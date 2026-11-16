<div class="form-grid">
    <div class="field">
        <label for="name">Nama lengkap <span aria-hidden="true">*</span></label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required autocomplete="name">
        @error('name')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="email">Email <span aria-hidden="true">*</span></label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email">
        @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="role">Tingkat akses <span aria-hidden="true">*</span></label>
        <select id="role" name="role" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role ?? 'kasir') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="password">Kata sandi {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}{{ $user->exists ? '' : '*' }}</label>
        <input id="password" name="password" type="password" minlength="10" @required(!$user->exists) autocomplete="new-password">
        @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="password_confirmation">Konfirmasi kata sandi {{ $user->exists ? '(jika mengganti kata sandi)' : '*' }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="10" @required(!$user->exists) autocomplete="new-password">
    </div>
</div>