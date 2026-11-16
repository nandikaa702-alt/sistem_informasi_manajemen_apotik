<div class="form-grid">
    <div class="field">
        <label for="code">Kode obat <span aria-hidden="true">*</span></label>
        <input id="code" name="code" value="{{ old('code', $medicine->code) }}" maxlength="50" required autocomplete="off">
        @error('code') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="name">Nama obat <span aria-hidden="true">*</span></label>
        <input id="name" name="name" value="{{ old('name', $medicine->name) }}" maxlength="150" required>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="category_id">Kategori obat <span aria-hidden="true">*</span></label>
        <select id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $medicine->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @if ($categories->isEmpty())
            <div class="field-error">Belum ada kategori. <a href="{{ route('categories.create') }}">Tambah kategori</a> terlebih dahulu.</div>
        @endif
        @error('category_id') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="type">Jenis obat <span aria-hidden="true">*</span></label>
        <input id="type" name="type" value="{{ old('type', $medicine->type) }}" maxlength="100" placeholder="Contoh: Tablet" required>
        @error('type') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="unit">Satuan obat <span aria-hidden="true">*</span></label>
        <input id="unit" name="unit" value="{{ old('unit', $medicine->unit) }}" maxlength="50" placeholder="Contoh: Strip" required>
        @error('unit') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="supplier_id">Pemasok <span aria-hidden="true">*</span></label>
        <select id="supplier_id" name="supplier_id" required>
            <option value="">Pilih pemasok</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $medicine->supplier_id) === (string) $supplier->id)>{{ $supplier->code }} · {{ $supplier->name }}</option>
            @endforeach
        </select>
        @if ($suppliers->isEmpty())
            <div class="field-error">Belum ada pemasok. <a href="{{ route('suppliers.create') }}">Tambah pemasok</a> terlebih dahulu.</div>
        @endif
        @error('supplier_id') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="purchase_price">Harga beli (Rp) <span aria-hidden="true">*</span></label>
        <input id="purchase_price" name="purchase_price" type="number" min="0" step="0.01" value="{{ old('purchase_price', $medicine->purchase_price) }}" required>
        @error('purchase_price') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="sale_price">Harga jual (Rp) <span aria-hidden="true">*</span></label>
        <input id="sale_price" name="sale_price" type="number" min="0" step="0.01" value="{{ old('sale_price', $medicine->sale_price) }}" required>
        @error('sale_price') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    @if ($medicine->exists)
        <div class="field">
            <label>Stok saat ini</label>
            <p>{{ number_format($medicine->stock, 0, ',', '.') }} {{ $medicine->unit }} · <a href="{{ route('stock.index') }}">Kelola stok</a></p>
        </div>
    @else
        <div class="field">
            <label for="stock">Stok awal <span aria-hidden="true">*</span></label>
            <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', 0) }}" required>
            @error('stock') <div class="field-error">{{ $message }}</div> @enderror
        </div>
    @endif
    <div class="field">
        <label for="minimum_stock">Batas stok minimum <span aria-hidden="true">*</span></label>
        <input id="minimum_stock" name="minimum_stock" type="number" min="0" step="1" value="{{ old('minimum_stock', $medicine->minimum_stock ?? 10) }}" required>
        @error('minimum_stock') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="expires_at">Tanggal kedaluwarsa <span aria-hidden="true">*</span></label>
        <input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at', $medicine->expires_at?->format('Y-m-d')) }}" required>
        @error('expires_at') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field full">
        <label for="description">Deskripsi obat</label>
        <textarea id="description" name="description" maxlength="2000" placeholder="Informasi tambahan tentang obat">{{ old('description', $medicine->description) }}</textarea>
        @error('description') <div class="field-error">{{ $message }}</div> @enderror
    </div>
</div>