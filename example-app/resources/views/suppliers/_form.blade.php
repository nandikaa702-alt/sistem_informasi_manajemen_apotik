<div class="form-grid">
    <div class="field">
        <label for="code">Kode pemasok <span aria-hidden="true">*</span></label>
        <input id="code" name="code" value="{{ old('code', $supplier->code) }}" maxlength="50" required autocomplete="off">
        @error('code')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="name">Nama pemasok <span aria-hidden="true">*</span></label>
        <input id="name" name="name" value="{{ old('name', $supplier->name) }}" maxlength="150" required>
        @error('name')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="company_name">Nama perusahaan <span aria-hidden="true">*</span></label>
        <input id="company_name" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" maxlength="180" required>
        @error('company_name')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="phone">Nomor telepon <span aria-hidden="true">*</span></label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $supplier->phone) }}" maxlength="30" required autocomplete="tel">
        @error('phone')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field full">
        <label for="email">Email <span aria-hidden="true">*</span></label>
        <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}" maxlength="150" required autocomplete="email">
        @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field full">
        <label for="address">Alamat <span aria-hidden="true">*</span></label>
        <textarea id="address" name="address" maxlength="2000" required autocomplete="street-address">{{ old('address', $supplier->address) }}</textarea>
        @error('address')<div class="field-error">{{ $message }}</div>@enderror
    </div>
</div>