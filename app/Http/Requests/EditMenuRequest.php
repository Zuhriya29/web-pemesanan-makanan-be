<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'nama_menu' => 'sometimes|required|string|max:200',
        'harga' => 'sometimes|required|integer|min:0',
        'kategori' => 'sometimes|required|in:makanan,minuman',
        'status' => 'sometimes|required|in:available,unvailable',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ];
}

    public function messages(): array
    {
        return [
            'nama_menu.required' => 'Nama menu belum diisi',
            'harga.required' => 'Harga belum diisi',
            'harga.integer' => 'Harga harus berupa angka',
            'kategori.required' => 'Kategori belum dipilih',
            'kategori.in' => 'Kategori tidak valid',
            'gambar.required' => 'Gambar menu belum dimasukkan',
            'gambar.image' => 'File harus berformat jpeg, png, jpg',
            'gambar.max' => 'Ukuran gambar maksimal 1MB',
        ];
    }
}