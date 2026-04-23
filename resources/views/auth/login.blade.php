<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 400px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: 0.2s;
        }

        .input-group input:focus {
            border-color: #4f46e5;
        }

        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }

        .remember {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .remember input {
            margin-right: 8px;
        }

        .forgot {
            text-align: right;
            margin-bottom: 15px;
        }

        .forgot a {
            text-decoration: none;
            color: #4f46e5;
            font-size: 13px;
        }

        .forgot a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: #4f46e5;
            border: none;
            color: white;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #4338ca;
        }

        .status {
            text-align: center;
            color: green;
            margin-bottom: 10px;
            font-size: 14px;
        }

    </style>
</head>
<body>

<div class="container">
    <div class="card">

        <div class="title">Login</div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Remember -->
            <div class="remember">
                <input type="checkbox" name="remember">
                <span>Remember me</span>
            </div>

            <!-- Forgot Password -->
            @if (Route::has('password.request'))
                <div class="forgot">
                    <a href="{{ route('password.request') }}">
                        Forgot your password?
                    </a>
                </div>
            @endif

            <!-- Button -->
            <button type="submit" class="btn">
                Log in
            </button>

        </form>

    </div>
</div>

</body>
</html>