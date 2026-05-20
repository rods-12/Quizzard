<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login — Quizzard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #FAF6EC;
            background-image:
                radial-gradient(ellipse at 20% 20%, rgba(91,42,155,0.10) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 80%, rgba(58,26,107,0.08) 0%, transparent 60%);
            -webkit-font-smoothing: antialiased;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(42,18,71,0.08), 0 16px 48px rgba(42,18,71,0.14);
            border: 1px solid rgba(91,42,155,0.10);
            overflow: hidden;
        }

        /* ── Header hero strip ── */
        .login-header {
            background: linear-gradient(135deg, #5B2A9B 0%, #3A1A6B 100%);
            padding: 36px 32px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .login-header img {
            height: 64px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.25));
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .login-header p {
            font-size: 13px;
            font-weight: 500;
            color: rgba(237,231,242,0.70);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: -8px;
        }

        /* ── Form body ── */
        .login-body {
            padding: 32px;
        }

        /* ── Error banner ── */
        .error-banner {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 20px;
            padding: 12px 14px;
            background: rgba(239,68,68,0.07);
            border: 1px solid rgba(239,68,68,0.22);
            border-radius: 10px;
            color: #EF4444;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.5;
        }

        /* ── Form fields ── */
        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }

        .field label {
            font-size: 13px;
            font-weight: 600;
            color: #1F1235;
            letter-spacing: -0.01em;
        }

        .field input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid rgba(91,42,155,0.18);
            background: #FAF6EC;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: #1F1235;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input::placeholder { color: #A99BC4; }
        .field input:focus {
            border-color: #5B2A9B;
            box-shadow: 0 0 0 3px rgba(91,42,155,0.12);
            background: #FFFFFF;
        }

        /* ── Password wrapper ── */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            /* extra right padding so text never slides under the toggle */
            padding-right: 44px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            color: #A99BC4;
            line-height: 0;
            transition: color 0.15s, background 0.15s;
            flex-shrink: 0;
        }
        .password-toggle:hover {
            color: #5B2A9B;
            background: rgba(91,42,155,0.08);
        }
        .password-toggle svg { width: 18px; height: 18px; }

        /* ── Login button ── */
        #login-btn {
            width: 100%;
            margin-top: 8px;
            padding: 12px 18px;
            border-radius: 10px;
            border: none;
            background: #F2C94C;
            color: #1F1235;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.01em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
        }
        #login-btn:hover:not(:disabled) {
            background: #E0A93B;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(242,201,76,0.38);
        }
        #login-btn:disabled {
            opacity: 0.60;
            cursor: not-allowed;
        }

        /* ── Divider footer ── */
        .login-footer {
            padding: 0 32px 24px;
            text-align: center;
            font-size: 12px;
            color: #A99BC4;
        }
    </style>
</head>
<body>

    <div class="login-card">

        {{-- Hero header --}}
        <div class="login-header">
            <img src="{{ asset('images/quizzard-logo.png') }}" alt="Quizzard logo">
            <h1>Welcome back</h1>
            <p>Teacher Portal</p>
        </div>

        {{-- Form body --}}
        <div class="login-body">

            @if($errors->any())
                <div class="error-banner">
                    <svg style="width:16px;height:16px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('teacher.login.submit') }}" method="POST">
                @csrf

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@school.edu"
                        autocomplete="email"
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            id="toggle-password"
                            aria-label="Show password"
                        >
                            {{-- Eye icon (shown when password is hidden) --}}
                            <svg id="icon-eye" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{-- Eye-off icon (shown when password is visible) --}}
                            <svg id="icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.03-3.41M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.293 5.218M6.53 6.53L3 3m3.53 3.53l11.94 11.94M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button id="login-btn" type="submit">
                    Sign in
                </button>
            </form>

        </div>

        <div class="login-footer">
            Quizzard &mdash; Teacher Edition
        </div>

    </div>

    <script>
        // ── Password visibility toggle ──────────────────────────
        (function () {
            const toggle  = document.getElementById('toggle-password');
            const input   = document.getElementById('password');
            const iconEye    = document.getElementById('icon-eye');
            const iconEyeOff = document.getElementById('icon-eye-off');

            toggle.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                iconEye.style.display    = isHidden ? 'none'  : '';
                iconEyeOff.style.display = isHidden ? ''      : 'none';
                toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        })();

        // ── Submit spinner ──────────────────────────────────────
        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('login-btn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin" style="width:18px;height:18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Signing in…
            `;
        });
    </script>

</body>
</html>
