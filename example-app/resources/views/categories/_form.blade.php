<div class="form-grid">
    <div class="field full">
        <label for="name">Nama kategori <span aria-hidden="true">*</span></label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" maxlength="100" required autofocus>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field full">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" maxlength="1000" placeholder="Keterangan kategori (opsional)">{{ old('description', $category->description) }}</textarea>
        @error('description') <div class="field-error">{{ $message }}</div> @enderror
    </div>
</div>