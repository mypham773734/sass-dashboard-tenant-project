<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Application\User\DTOs\{
    ChangePasswordDTO, 
    UpdateProfileDTO
};
use App\Application\User\UseCases\{
    ChangePasswordUseCase, 
    GetProfileUseCase, 
    UpdateProfileUseCase
};

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
            $userId = authContext()->getId(); 
            $profile = $this->getProfileUseCase->execute($userId);
            $tenantId = tenantContext()->getId(); 
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
                $userId = authContext()->getId(); 
                $profile   = $this->getProfileUseCase->execute($userId);
                $oldAvatar = $profile->avatar;

                if ($oldAvatar) {
                    Storage::disk('public')->delete($oldAvatar);
                }

                $ext        = $request->file('avatar')->getClientOriginalExtension();
                $avatarPath = $request->file('avatar')
                    ->storeAs('avatars', $userId . '.' . $ext, 'public');
            }

            $dto = UpdateProfileDTO::fromArray(
                array_merge($request->validated(), ['avatar_path' => $avatarPath])
            );

            $this->updateProfileUseCase->execute($dto, $userId);

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
            $userId = authContext()->getId(); 
            $dto = ChangePasswordDTO::fromArray($request->validated());
            $this->changePasswordUseCase->execute($dto, $userId);

            return redirect()->route('profile.show')->with('success', 'Password changed successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to change password.');
        }
    }
}
