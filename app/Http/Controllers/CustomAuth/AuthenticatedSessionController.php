<?php 

namespace App\Http\Controllers\CustomAuth; 
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request; 

class AuthenticatedSessionController{
    public function create(){
        return view('custom-auth.login'); 
    }

    public function store(LoginRequest $request){
        $credentials = [
            'email' => $request->email, 
            'password' => $request->password, 
        ]; 

        if(Auth::attempt($credentials)){
            $request->session()->regenerate(); 
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->withErrors([
            'message' => 'Thông tin đăng nhập không chính xác'
        ]); 
    }

    public function destroy(Request $request){
        Auth::guard('web')->logout(); 

        $request->session()->invalidate();
        $request->session()->regenerate(); 

        return redirect('/'); 
    }
}