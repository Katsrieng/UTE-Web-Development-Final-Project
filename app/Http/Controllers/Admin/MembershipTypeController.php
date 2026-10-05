<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipType;
use Illuminate\Http\Request;

class MembershipTypeController extends Controller
{
    public function index()
    {
        $membershipTypes = MembershipType::latest()->paginate(10);

        return view('admin.membership_types.index', compact('membershipTypes'));
    }

    public function create()
    {
        return view('admin.membership_types.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        MembershipType::create($validated);

        return redirect()
            ->route('admin.membership-types.index')
            ->with('success', 'Membership type created.');
    }

    public function edit(MembershipType $membershipType)
    {
        return view('admin.membership_types.edit', compact('membershipType'));
    }

    public function update(Request $request, MembershipType $membershipType)
    {
        $validated = $this->validateData($request);

        $membershipType->update($validated);

        return redirect()
            ->route('admin.membership-types.index')
            ->with('success', 'Membership type updated.');
    }

    public function destroy(MembershipType $membershipType)
    {
        if ($membershipType->memberships()->exists()) {
            return back()->with('error', 'Cannot delete: customers are enrolled in this membership type.');
        }

        $membershipType->delete();

        return redirect()
            ->route('admin.membership-types.index')
            ->with('success', 'Membership type deleted.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);
    }
}
