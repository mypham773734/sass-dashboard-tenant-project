<?php

namespace App\Http\Controllers\Admin;

use App\Application\User\DTOs\ChangePasswordDTO;
use App\Application\User\DTOs\UpdateProfileDTO;
use App\Application\User\UseCases\ChangePasswordUseCase;
use App\Application\User\UseCases\GetProfileUseCase;
use App\Application\User\UseCases\UpdateProfileUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Shared\Tenant\TenantContext; 

class ProfileController extends Controller
{
    public function __construct(
        private readonly GetProfileUseCase     $getProfileUseCase,
        private readonly UpdateProfileUseCase  $updateProfileUseCase,
        private readonly ChangePasswordUseCase $changePasswordUseCase,
    ) {}

    public function show()
    {
        try {
            $profile = $this->getProfileUseCase->execute(auth()->id());
            $tenantId = app(TenantContext::class)->getId(); 
            return view('admin.pages.profile.index', compact('profile', 'tenantId'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load profile.');
        }
    }

    public function update(UpdateProfileRequest $request)
    {
        try {
            $avatarPath = null;

            if ($request->hasFile('avatar')) {
                $profile   = $this->getProfileUseCase->execute(auth()->id());
                $oldAvatar = $profile->avatar;

                if ($oldAvatar) {
                    Storage::disk('public')->delete($oldAvatar);
                }

                $ext        = $request->file('avatar')->getClientOriginalExtension();
                $avatarPath = $request->file('avatar')
                    ->storeAs('avatars', auth()->id() . '.' . $ext, 'public');
            }

            $dto = UpdateProfileDTO::fromArray(
                array_merge($request->validated(), ['avatar_path' => $avatarPath])
            );

            $this->updateProfileUseCase->execute($dto, auth()->id());

            return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to update profile.')->withInput();
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $dto = ChangePasswordDTO::fromArray($request->validated());
            $this->changePasswordUseCase->execute($dto, auth()->id());

            return redirect()->route('profile.show')->with('success', 'Password changed successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to change password.');
        }
    }
}
