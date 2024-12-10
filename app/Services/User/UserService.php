<?php

namespace App\Services\User;

use App\Enums\UserRoleEnum;
use App\Enums\UserValidationEnum;
use App\Mail\AccountCreated;
use App\Models\PasswordRecovery;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryMail;
use App\Mail\ValidationAcceptedMail;
use App\Mail\ValidationRefusedMail;
use App\Mail\WelcomeMail;
use App\Models\UserAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserService
{

    public function all()
    {
        try {
            $users = User::get();

            return ['status' => true, 'data' => $users];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $role = $request->role;
            $status = $request->status;
            $validation = $request->validation;

            $users = User::with('attachments');

            if(isset($search_term)){
                $users->where('name', 'LIKE', "%{$search_term}%")
                    ->orWhere('email', 'LIKE', "%{$search_term}%");
            }

            if(isset($role)){
                $roles = explode(',' ,$role);
                $users->whereIn('role', $roles);
            }

            if(isset($status)){
                $status = explode(',' ,$status);
                $users->whereIn('status', $status);
            }

            if(isset($validation)){
                $validations = explode(',' ,$validation);
                $users->whereIn('validation', $validations);
            }

            $users = $users->paginate($perPage);

            return $users;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }  

    public function getUser()
    {
        try {
            $user = auth()->user();
    
            if ($user) {
                // Cast para o tipo correto
                $user = $user instanceof \App\Models\User ? $user : \App\Models\User::find($user->id);
    
                return ['status' => true, 'data' => $user];
            }
    
            return ['status' => false, 'error' => 'Usuário não autenticado', 'statusCode' => 401];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function getByEmail($request)
    {
        try {            

            if(!$request->filled('email')){
                throw new Exception('Email não enviado');
            }

            $user = User::where('email', $request->email)
                ->first();            

            if(!isset($user)){
                throw new Exception('Usuário não encontrado');
            }            

            return $user;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function cards()
    {
        try {
            $users = User::selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
                ->first();

            $users->inactive = $users->total - $users->active;

            return [
                'status' => true,
                'data' => [
                    'total' => $users->total,
                    'active' => $users->active,
                    'inactive' => $users->inactive
                ]
            ];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $request['is_active'] = $request['is_active'] == 'true' ? true : false;
            
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'surname' => ['required', 'string', 'max:255'],
                'company_name' => ['required', 'string', 'max:255'],
                'cnpj' => ['required', 'string', 'max:255'],
                'creci' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255'],                
                'is_active' => ['required', 'boolean'],
                'role' => ['required', 'in:Admin,Manager,Client'],
                'attachments' => ['nullable', 'array'],
                'password' => ['nullable', 'string'],
            ];

            $requestData = $request->all();

            if(!isset($requestData['password'])){
                $password = str_shuffle(Str::upper(Str::random(1)) . rand(0, 9) . Str::random(1, '?!@#$%^&*') . Str::random(5));
                $requestData['password'] = Hash::make($password);
            }
                
            $validator = Validator::make($requestData, $rules);
    
            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $usersWithEmailOrCnpj = User::where('email', $requestData['email'])
                ->orWhere('cnpj', $requestData['cnpj'])
                ->count();

            if($usersWithEmailOrCnpj){
                throw new Exception('Email ou Cnpj já existem no sistema.', 400);
            }

            $user = User::create($requestData);
    
            if(isset($request->attachments)){
                foreach($request->attachments as $attachment){
                    $attachment = is_array($attachment) ? $attachment : json_decode($attachment, true);
                    $path = $attachment['file']->store('attachments', 'public');
                    UserAttachment::firstOrCreate([
                        'id' => $attachment->getClientOriginalName(),
                    ], [
                        'category' => $attachment['category'],
                        'filename' => $attachment['file']->getClientOriginalName(),
                        'path' => $path,
                        'user_id' => Auth::user()->id,
                    ]);
                }
            }

            if ($request->role == UserRoleEnum::Client->value) {
                Mail::to($user->email)->send(new WelcomeMail($user->name));
            }else{
                Mail::to($user->email)->send(new AccountCreated($user->name, $user->email, $password));
            }
    
            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
    
    public function update($request, $id)
    {
        try {
            $request['is_active'] = $request['is_active'] == 'true' ? true : false;

            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'surname' => ['required', 'string', 'max:255'],
                'company_name' => ['required', 'string', 'max:255'],
                'cnpj' => ['required', 'string', 'max:255'],
                'creci' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:255'],
                'is_active' => ['required', 'boolean'],
                'role' => ['required', 'in:Admin,Manager,Client'],
                'attachments' => ['nullable', 'array'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $userToUpdate = User::find($id);

            if(!isset($userToUpdate)) throw new Exception('Usuário não encontrado');

            $requestData = $validator->validated();

            $userToUpdate->update($requestData);

            if(isset($request->attachments)){
                foreach($request->attachments as $attachment){
                    $attachment = is_array($attachment) ? $attachment : json_decode($attachment, true);
                    $path = $attachment['file']->store('attachments', 'public');
                    UserAttachment::firstOrCreate([
                        'id' => $attachment->getClientOriginalName(),
                    ], [
                        'category' => $attachment['category'],
                        'filename' => $attachment['file']->getClientOriginalName(),
                        'path' => $path,
                        'user_id' => Auth::user()->id,
                    ]);
                }
            }

            return ['status' => true, 'data' => $userToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id){
        try{
            $user = User::find($id);

            if(!$user) throw new Exception('Usuário não encontrado');

            $userName = $user->name;
            $user->delete();

            return ['status' => true, 'data' => $userName];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function validate($request, $id)
    {
        try {

            $rules = [
                'validation' => ['required', 'string', 'in:Pending,Accepted,Refused'],
                'justification' => ['nullable', 'string', 'max:1000'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            $userToUpdate = User::find($id);

            if(!isset($userToUpdate)) throw new Exception('Usuário não encontrado');

            $requestData = $validator->validated();                   

            $userToUpdate->update($requestData);

            if($request->validation == UserValidationEnum::Accepted->value){
                $password = Str::random(20);
                $userToUpdate->password = Hash::make($password);
                $userToUpdate->is_active = true;
                $userToUpdate->save();
                Mail::to($userToUpdate->email)
                    ->send(new ValidationAcceptedMail($userToUpdate->name, $userToUpdate->email, $password));
            }

            if($request->validation == UserValidationEnum::Refused->value){                
                Mail::to($userToUpdate->email)
                ->send(new ValidationRefusedMail($userToUpdate->name, $request->justification));
            }

            return ['status' => true, 'data' => $userToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function deleteAttachment($id){
        try{
            $userAttachment = UserAttachment::find($id);

            if(!$userAttachment) throw new Exception('Anexo não encontrado');

            $userAttachmentName = $userAttachment->filename;
            $userAttachment->delete();

            return ['status' => true, 'data' => $userAttachmentName];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function userBlock($user_id)
    {
        try {
            $user = User::find($user_id);

            if (!$user) throw new Exception('Usuário não encontrado');

            $user->is_active = !$user->is_active;
            $user->save();

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function requestRecoverPassword($request)
    {
        try {
            $email = $request->email;
            $user = User::where('email', $email)->first();

            if (!isset($user)) throw new Exception('Usuário não encontrado.');

            $code = bin2hex(random_bytes(10));

            $recovery = PasswordRecovery::create([
                'code' => $code,
                'user_id' => $user->id
            ]);

            if (!$recovery) {
                throw new Exception('Erro ao tentar recuperar senha');
            }

            Mail::to($email)->send(new PasswordRecoveryMail($code));
            return ['status' => true, 'data' => $user];

        } catch (Exception $error) {
            Log::error('Erro na recuperação de senha: ' . $error->getMessage());
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function updatePassword($request){
        try{
            $code = $request->code;
            $password = $request->password;

            $recovery = PasswordRecovery::orderBy('id', 'desc')->where('code', $code)->first();

            if(!$recovery) throw new Exception('Código enviado não é válido.');

            $user = User::find($recovery->user_id);
            $user->password = Hash::make($password);
            $user->save();
            $recovery->delete();

            return ['status' => true, 'data' => $user];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}