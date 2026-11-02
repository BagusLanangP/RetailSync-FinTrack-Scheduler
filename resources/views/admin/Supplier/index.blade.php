@extends('admin.layouts.app')

@section('title', 'Supplier List')

@section('content')

    <!-- Header Actions -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Manajemen Supplier</h2>
            <p class="text-xs text-slate-500 mt-1">Daftar mitra supplier yang menyediakan barang dan inventaris sistem.</p>
        </div>
        <a href="{{ route('admin.create-supplier') }}" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl text-center shadow-md shadow-emerald-500/10 transition duration-150 active:scale-[0.98]">
            + Buat Supplier
        </a>
    </div>

    <!-- Filter Card -->
    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm mb-6">
        <form method="GET" action="{{ route('supplier_index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="w-full sm:w-64 space-y-1.5">
                <label for="jenis" class="block text-xs font-semibold text-slate-500 uppercase">Filter Jenis Barang</label>
                <select name="jenis" id="jenis" class="w-full border border-slate-200/80 bg-slate-50/50 rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 outline-none transition duration-150">
                    <option value="">-- Semua Jenis --</option>
                    @foreach($JenisBarangs as $jenis)
                        <option value="{{ $jenis->id }}" {{ request('jenis') == $jenis->id ? 'selected' : '' }}>
                            {{ $jenis->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-xl transition duration-150">
                Filter Data
            </button>
            @if(request('jenis'))
                <a href="{{ route('supplier_index') }}" class="w-full sm:w-auto px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-xl text-center transition duration-150">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Supplier Table Container -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600">
                <thead class="text-xs uppercase bg-slate-50 text-slate-400 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 w-[5%]">No</th>
                        <th class="px-6 py-4 w-[20%]">Nama Supplier</th>
                        <th class="px-6 py-4 w-[12%]">Jenis Barang</th>
                        <th class="px-6 py-4 w-[12%]">Date Joined</th>
                        <th class="px-6 py-4 w-[17%]">Catatan</th>
                        <th class="px-6 py-4 w-[34%] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($data as $s)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ !$s->status_aktif ? 'bg-slate-50/70 text-slate-400' : '' }}" data-status="{{ $s->status_aktif ? 'true' : 'false' }}">
                            <td class="px-6 py-4 font-semibold text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 {{ !$s->status_aktif ? 'text-slate-400 line-through' : '' }}">
                                    {{ $s->nama }}
                                </div>
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $s->nomor_whatsapp }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg border {{ $s->status_aktif ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-slate-100 text-slate-400 border-slate-200' }}">
                                    {{ $s->jenisBarang->name ?? 'Tidak Ada Jenis' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                {{ \Carbon\Carbon::parse($s->dari_tanggal)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 italic max-w-xs truncate">
                                {{ $s->catatan ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right flex flex-wrap justify-end gap-1.5 items-center">
                                
                                <!-- Show details (redirects to inventory list) -->
                                <a href="{{ route('supplier.show', $s->id) }}" 
                                   class="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition duration-150"
                                   title="Lihat Detail Barang Supplier">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Detail</span>
                                </a>

                                <!-- Edit Button -->
                                <a href="{{ route('supplier.edit', $s->id) }}" 
                                   class="flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-xs font-semibold transition duration-150"
                                   title="Edit Supplier">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 20.062a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                    <span>Edit</span>
                                </a>

                                <!-- Toggle Status Button -->
                                <form action="{{ route('supplier.toggleStatus', $s->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 {{ $s->status_aktif ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}" 
                                            title="{{ $s->status_aktif ? 'Nonaktifkan Supplier' : 'Aktifkan Supplier' }}">
                                        @if ($s->status_aktif)
                                            <!-- Icon Speaker with X (mute) -->
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6L4.5 9H1.5v6h3l4.5 3.75V3.75z" />
                                            </svg>
                                        @else
                                            <!-- Icon Speaker (active) -->
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                                            </svg>
                                        @endif
                                        <span>{{ $s->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                                    </button>
                                </form>

                                <!-- Delete Button -->
                                <form action="{{ route('supplier.destroy', $s->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold transition duration-150" title="Hapus Supplier">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection