                                <form method="POST" action="{{ $loginAction }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                                   class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                                            <label class="form-check-label" for="remember">Remember me</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-hotel w-100">{{ $loginButton }}</button>
                                </form>
