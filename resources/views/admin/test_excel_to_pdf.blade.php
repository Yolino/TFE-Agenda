@extends('app')

@section('title', 'Test Excel to PDF')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Tester la conversion Excel en PDF</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.excelToPdf') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label for="excel_file" class="block text-sm font-medium text-gray-700">Fichier Excel</label>
            <input type="file" name="excel_file" id="excel_file" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Convertir en PDF</button>
    </form>
</div>
@endsection