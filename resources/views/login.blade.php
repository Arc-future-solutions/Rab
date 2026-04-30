<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login — RAB Consulting</title>
    
    <!-- Typography: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/styles.css"/>
    
    <style>
        body {
            background-color: var(--bg-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            font-family: var(--font-inter);
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: white;
            padding: 48px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--slate-200);
            box-shadow: var(--shadow-lg);
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .brand-logo {
            height: 52px;
            width: auto;
        }
        .brand-name {
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: 0.05em;
            color: var(--primary);
            line-height: 1;
        }
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--slate-700);
        }
        form input[type="email"],
        form input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--slate-200);
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-family: inherit;
        }
        form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--bg-highlight);
        }
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .form-footer label {
            margin-bottom: 0;
            font-weight: 400;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .form-footer a {
            font-size: 0.85rem;
            color: var(--secondary);
        }
        .form-footer a:hover {
            color: var(--primary);
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <a href="/" class="brand">
            <img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
            
        </a>
        <h2>Welcome Back</h2>
        <p style="color: var(--slate-400); font-size: 0.95rem; margin-top: 8px;">Access the advisory portal and your assessment results.</p>
    </div>

    @if($errors->any())
        <div style="background: var(--bg-highlight); border: 1px solid var(--slate-200); padding: 12px 16px; border-radius: var(--radius); margin-bottom: 24px; font-size: 0.85rem; color: var(--critical);">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="/login" method="POST">
        @csrf
        <div>
            <label for="email">Work Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
        </div>
        
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>

        <div class="form-footer">
            <label>
                <input type="checkbox" name="remember"> Remember me
            </label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">Sign In to Portal</button>
    </form>

   
</div>

</body>
</html>
