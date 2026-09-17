<?php

namespace App\Livewire\Users;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Index extends Component
{
    use PasswordValidationRules, ProfileValidationRules, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Reset to the first page whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Get the paginated list of users matching the search term.
     *
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }

    /**
     * Open the form modal to create a new user.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('user-form')->show();
    }

    /**
     * Open the form modal to edit an existing user.
     */
    public function edit(User $user): void
    {
        $this->resetForm();

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;

        Flux::modal('user-form')->show();
    }

    /**
     * Create or update the user.
     */
    public function save(): void
    {
        $rules = $this->profileRules($this->editingId);

        // The password is optional when editing; leave it blank to keep the current one.
        $rules['password'] = $this->editingId !== null && $this->password === ''
            ? ['nullable']
            : $this->passwordRules();

        $validated = $this->validate($rules);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        if ($this->editingId === null) {
            User::create($validated);

            Flux::toast(variant: 'success', text: __('User created.'));
        } else {
            User::findOrFail($this->editingId)->update($validated);

            Flux::toast(variant: 'success', text: __('User updated.'));
        }

        Flux::modal('user-form')->close();

        $this->resetForm();
    }

    /**
     * Open the confirmation modal to delete a user.
     */
    public function confirmDelete(User $user): void
    {
        $this->deletingId = $user->id;

        Flux::modal('delete-user')->show();
    }

    /**
     * Delete the selected user.
     */
    public function delete(): void
    {
        $user = User::findOrFail($this->deletingId);

        if ($user->is(Auth::user())) {
            Flux::toast(variant: 'danger', text: __('You cannot delete your own account here.'));

            return;
        }

        $user->delete();

        $this->deletingId = null;

        Flux::modal('delete-user')->close();

        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.users.index');
    }

    /**
     * Clear the form fields and validation errors.
     */
    protected function resetForm(): void
    {
        $this->reset('editingId', 'name', 'email', 'password', 'password_confirmation');

        $this->resetValidation();
    }
}
