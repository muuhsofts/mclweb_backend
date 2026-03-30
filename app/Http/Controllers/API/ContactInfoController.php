<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactInfoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show','contactInfo']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contacts = ContactInfo::with('contactUs')->latest()->get();
        return response()->json(['contact_infos' => $contacts], 200);
    }



    public function contactInfo()
    {
        $contacts = ContactInfo::with('contactUs')->latest()->get();
        return response()->json(['contact_infos' => $contacts], 200);
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contactus_id' => 'required',
            'phone_one' => 'required|string|max:25',
            'phone_two' => 'nullable|string|max:25',
            'email_address' => 'required|email|max:255|unique:contact_info,email_address',
            'webmail_address' => 'nullable|email|max:255',
            'location' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $contact = ContactInfo::create($validator->validated());

        return response()->json(['message' => 'Contact Info created successfully', 'contact_info' => $contact->load('contactUs')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ContactInfo $contactInfo)
    {
        return response()->json(['contact_info' => $contactInfo->load('contactUs')], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ContactInfo $contactInfo)
    {
        $validator = Validator::make($request->all(), [
            'contactus_id' => 'nullable',
            // 'department_category' validation has been removed.
            'phone_one' => 'nullable',
            'phone_two' => 'nullable',
            'email_address' => [
                'required',
                'email',
                'max:255',
                Rule::unique('contact_info')->ignore($contactInfo->contact_info_id, 'contact_info_id'),
            ],
            'webmail_address' => 'nullable',
            'location' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $contactInfo->update($validator->validated());

        return response()->json(['message' => 'Contact Info updated successfully', 'contact_info' => $contactInfo->load('contactUs')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContactInfo $contactInfo)
    {
        $contactInfo->delete();
        return response()->json(['message' => 'Contact Info deleted successfully'], 200);
    }
}