<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class FacilityController extends Controller
{
    public function index()
    {
        $facilities = Facility::latest()->paginate(10);
        return view('facilities.index', compact('facilities'));
    }

    public function create()
    {
        return view('facilities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:open,closed,maintenance',
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        unset($validated['image']);
        $path = null;
        try {
            DB::transaction(function () use ($request, $validated, &$path): void {
                $facility = Facility::create($validated);
                if ($request->hasFile('image')) {
                    $path = $request->file('image')->store('facilities/'.$facility->id, 'public');
                    if (! $path) {
                        throw new RuntimeException('Unable to store facility photo.');
                    }
                    $facility->update(['image' => $path]);
                }
            });
        } catch (Throwable $exception) {
            if ($path) { Storage::disk('public')->delete($path); }
            throw $exception;
        }

        return redirect()->route('management.facilities.index')->with('success', 'Facility created successfully.');
    }

    public function show(Facility $facility)
    {
        return view('facilities.show', compact('facility'));
    }

    public function edit(Facility $facility)
    {
        return view('facilities.edit', compact('facility'));
    }

    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:open,closed,maintenance',
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        unset($validated['image']);
        $path = null;
        try {
            DB::transaction(function () use ($request, $facility, $validated, &$path): void {
                $locked = Facility::lockForUpdate()->findOrFail($facility->id);
                $oldPath = $locked->image;
                if ($request->hasFile('image')) {
                    $path = $request->file('image')->store('facilities/'.$locked->id, 'public');
                    if (! $path) {
                        throw new RuntimeException('Unable to store facility photo.');
                    }
                    $validated['image'] = $path;
                }
                $locked->update($validated);
                if ($path && $oldPath) {
                    DB::afterCommit(fn () => $this->cleanupImage($locked->id, $oldPath));
                }
            });
        } catch (Throwable $exception) {
            if ($path) { Storage::disk('public')->delete($path); }
            throw $exception;
        }

        return redirect()->route('management.facilities.index')->with('success', 'Facility updated successfully.');
    }

    public function destroy(Facility $facility)
    {
        DB::transaction(function () use ($facility): void {
            $locked = Facility::lockForUpdate()->findOrFail($facility->id);
            $path = $locked->image;
            $id = $locked->id;
            $locked->delete();
            if ($path) {
                DB::afterCommit(fn () => $this->cleanupImage($id, $path));
            }
        });

        return redirect()->route('management.facilities.index')->with('success', 'Facility deleted successfully.');
    }

    private function cleanupImage(int $facilityId, string $path): void
    {
        // Only this facility's generated upload path is owned by this CRUD.
        if (! preg_match('~^facilities/'.$facilityId.'/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)$~i', $path)
            || Facility::where('image', $path)->exists()) {
            return;
        }
        try {
            if (! Storage::disk('public')->delete($path)) {
                report(new RuntimeException('Unable to remove facility photo: '.$path));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
