<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;



class UserController extends Controller
{

    public function user(Request $request)
    {
        return $request->user();
    }

    public function register(Request $request)
    {

        $validator =  Validator::make($request->all(), [
            'email' => 'required|unique:users',
            'name' => 'required',
            // 'password' => ['required|min:6'],

        ]);

        if ($validator->fails()) {
            return Response::json([
                'code' => 400,
                'message' => 'failed',
                'data' => $validator->messages(),
            ]);
        } else {
            $name = $request->name;
            $email = $request->email;
            $password = $request->password;

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password)
            ]);
            // send verification email
            $user->sendEmailVerificationNotification();

            return Response::json([
                'code' => 200,
                'message' => 'Verification email sent. Please check your email.',
                'data' => $user,
            ]);
        }
    }

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!($user['email_verified_at'] == null)) {
            # code...
            if (!$user || !Hash::check($request->password, $user->password)) {
                return Response::json([
                    'code' => 400,
                    'messgae' => 'The provided credentials are incorrect.',
                    'data' => [],
                ]);
            }

            return Response::json([
                'code' => 200,
                'messgae' => 'success',
                'data' => [
                    'token' => $user->createToken('defualt')->plainTextToken,
                    'name' => $user->name,
                    'email' => $user->email,

                ],
            ]);
        } else {
            # code...
            return Response::json([
                'code' => 400,
                'messgae' => 'Your email address is not verified.',
                'data' => [],
            ]);
        }

        
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->tokens()->delete();

        return Response::json([
            'code' => 200,
            'message' => 'success',
            'data' => $user,
        ]);
    }

    public function changePassword(Request $request)
    {

        $newPassword = $request->newPassword;
        // $oldPassword = $request->oldPassword;
        $user = $request->user();

        if (Hash::check($request->oldPassword, $user->password)) {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);
            return Response::json($user);
        } else {
            return Response::json([
                'message' => 'old Password dosent correct'
            ]);
        }

        // $user_id =  $request->user()->id;
        // $user=User::findOrFail($user_id);

    }
    public function changeName(Request $request)
    {
        // $newName = $request->newName;
        // $Password = $request->Password;

        $user = $request->user();

        if (Hash::check($request->password, $user->password)) {
            $user->update([
                'name' =>  $request->newName,
            ]);
            return Response::json($user);
        }
        return Response::json([
            'message' => 'Password dosent correct'
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {

        $validatedData = $request->validate(['email' => 'required|email']);

        $response = Password::sendResetLink($validatedData);

        return response()->json([
            'code' => 200,
            // 'reset password' => url(route('resetPassword')),
            'msg' => "Reset link sent to your email",
            'data' => [],
        ]);


        // return response()->json(['message' => 'Reset link sent to your email']);
    }



    public function resetPassword(Request $request)
    {

        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|',
            'token' => 'required'
        ]);

        // Find the user associated with the email
        $user = User::where('email', $validatedData['email'])->first();
        // return $validatedData['token'];
        // If the user is found and the token is valid
        if ($user && $validatedData['email'] === $user->email) {
            // Update the user's password
            $user->password = Hash::make($validatedData['password']);
            $user->save();

            return response()->json(['message' => 'Password reset successfully'], 200);
        } else {
            return response()->json(['error' => 'Invalid token or email'], 401);
        }




        //         public function reset(Request $request)
        // {
        //     $validatedData = $request->validate([
        //         'email' => 'required|email',
        //         'password' => 'required|confirmed',
        //         'token' => 'required',
        //     ]);

        //     // Find the user by email
        //     $user = User::whereEmail($validatedData['email'])->first();

        //     // Check if the provided token matches the one stored in the user's record
        //     if($user && $validatedData['token'] === $user->getRememberToken())
        //     {
        //         // Update the user's password
        //         $user->password = Hash::make($validatedData['password']);
        //         $user->save();

        //         return response()->json(['message' => 'Password reset successfully'], 200);
        //     }
        //     else
        //     {
        //         return response()->json(['message' => 'Invalid token or email'], 400);
        //     }
        // }















        // $validatedData = $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required|min:6',
        //     // 'token' => 'required',
        // ]);
        // // return request()->password;
        // $response = Password::reset($validatedData, function ($user, $password) {
        //     $user->password = bcrypt($password);
        //     $user->save();
        // });

        // return Response::json([
        //     'code' => 200,
        //     'message' => 'Password reset successfully',

        //     'data' => [
        //         // 'token'=>request()->token,
        //     ],


        // ]);

        //     $status = Password::reset(


        //         $request->only('password',),
        //         function ($user, $password) {
        //             $user->forceFill([
        //                 'password' => Hash::make($password)
        //             ]);

        //             $user->save();
        //  return 'fghjkl';
        //             return event(new PasswordReset($user));
        //         }
        //     );
        //    return 'ah';
        //     return $status === Password::PASSWORD_RESET
        //                 ? redirect()->route('login')->with('status', __($status))
        //                 : back()->withErrors(['email' => [__($status)]]);


        //---------------------------------------------------------------------------------------------------------------------
        // $newPassword = $request->newPassword;

        // $user = User::where('email', $request->email)->first();

        // $user->update([
        //     'password' => Hash::make($newPassword),
        // ]);
        // return Response::json([
        //     'code' => 200,
        //     'message' => 'Password Updated',
        //     'data' => [],
        // ]);
    }



    public function verifyEmail(Request $request)
    {


        if (!auth()->check()) {
            auth()->loginUsingId($request->route('id'));
        }

        if ($request->route('id') != $request->user()->getKey()) {
            throw new AuthorizationException();
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return 'success';
    }

    // public function sendForgetPasswordEmail()
    // {
    //     # code...
    // }



}
