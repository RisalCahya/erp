<div class="space-y-5">
    <div>
        <label for="sku" class="mb-1.5 block text-sm font-semibold text-slate-800">SKU <span class="text-red-600">*</span></label>
        <input id="sku" name="sku" type="text" value="{{ old('sku', $material->sku) }}" required maxlength="255" autocomplete="off"
               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('sku'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('sku')])>
        @error('sku') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
        <p class="mt-1.5 text-xs text-slate-500">SKU harus unik dan menjadi kode identitas material.</p>
    </div>

    <div>
        <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-800">Nama Material <span class="text-red-600">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $material->name) }}" required maxlength="255" autocomplete="off"
               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('name'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('name')])>
        @error('name') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="unit" class="mb-1.5 block text-sm font-semibold text-slate-800">Satuan <span class="text-red-600">*</span></label>
        <input id="unit" name="unit" type="text" value="{{ old('unit', $material->unit) }}" required maxlength="255" placeholder="Contoh: pcs, kg, liter"
               @class(['min-h-11 w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-4', 'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('unit'), 'border-slate-300 focus:border-blue-600 focus:ring-blue-100' => ! $errors->has('unit')])>
        @error('unit') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
        <input type="hidden" name="is_active" value="0">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $material->exists ? $material->is_active : true))
                   class="mt-0.5 size-4 rounded border-slate-300 text-blue-700 focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Material aktif</span>
                <span class="mt-0.5 block text-xs text-slate-500">Material nonaktif tetap tersimpan tetapi ditandai sebagai tidak aktif.</span>
            </span>
        </label>
        @error('is_active') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
</div>
