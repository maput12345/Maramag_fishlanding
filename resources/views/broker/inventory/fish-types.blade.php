<div>
    @php
        $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
            ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
            : false;
        $fishTypeUpdateUrlTemplate = route('broker.fish-types.update', ['id' => '__ID__']);
    @endphp
    <!-- Fish Types Tab Content -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Fish List</h2>
        </div>
        @unless($brokerViewReadOnly)
            <a href="{{ route('broker.inventory.index', ['tab' => 'fishTypes', 'modal' => 'create']) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center space-x-2">
                <x-heroicon-o-plus class="w-4 h-4" />
                <span>Add Fish</span>
            </a>
        @endunless
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-6 summary-strip-wrap">
        <div class="summary-strip summary-strip--three">
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">No. of Fish</p>
                <p class="summary-stat-value text-gray-900">{{ number_format($fishTypeSummary['assigned'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Used in Purchases</p>
                <p class="summary-stat-value text-blue-600">{{ number_format($fishTypeSummary['used'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">With Price Set</p>
                <p class="summary-stat-value text-green-600">{{ number_format($fishTypeSummary['with_prices'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Fish Name Modal (Create/Edit) -->
    @if((request('modal') === 'create' || request('modal') === 'edit') && $brokerViewReadOnly)
        <x-app-modal
            title="Support Actions Required"
            subtitle="Broker fish-name maintenance is read-only until an admin explicitly enables support actions."
            :close-url="route('broker.inventory.index', ['tab' => 'fishTypes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="space-y-6 py-2">
                <p class="text-sm text-gray-600">
                    This broker workspace is currently in read-only mode. Enable support actions first if you need to add, edit, or remove fish for this broker.
                </p>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishTypes']) }}"
                       class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Back
                    </a>
                    <form method="POST" action="{{ route('admin.broker-view.support.enable') }}" class="sm:w-auto">
                        @csrf
                        <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-amber-700 sm:w-auto">
                            Enable Support Actions
                        </button>
                    </form>
                </div>
            </div>
        </x-app-modal>
    @elseif(request('modal') === 'create' || request('modal') === 'edit')
        <x-app-modal
            :title="request('modal') === 'edit' ? 'Edit Fish' : 'Add Fish'"
            :subtitle="request('modal') === 'edit' ? 'Update the fish details for cleaner inventory setup.' : 'Create a fish with a clear label and optional description.'"
            :close-url="route('broker.inventory.index', ['tab' => 'fishTypes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-green-500 to-green-600 text-white shadow-sm">
                    <x-heroicon-o-tag class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ request('modal') === 'edit' ? route('broker.fish-types.update', request('edit')) : route('broker.fish-types.store') }}" method="POST" class="space-y-6">
                @csrf
                @if(request('modal') === 'edit')
                    @method('PUT')
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Fish <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', request('modal') === 'edit' && isset($editingFishType) ? $editingFishType->display_name : '') }}"
                               placeholder="Enter fish, like Tilapia or Catfish"
                               class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors @error('name') border-red-500 @enderror"
                               required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <x-heroicon-o-tag class="h-5 w-5 text-gray-400" />
                        </div>
                    </div>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              placeholder="Add a short description to make the fish easier to recognize."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors resize-none @error('description') border-red-500 @enderror">{{ old('description', request('modal') === 'edit' && isset($editingFishType) ? $editingFishType->display_description : '') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishTypes']) }}"
                       class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto">
                        @if(request('modal') === 'edit')
                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-2" />
                            Update Fish
                        @else
                            <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                            Add Fish
                        @endif
                    </button>
                </div>
            </form>
        </x-app-modal>
    @endif

    <!-- Fish Search -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <form method="GET" action="{{ route('broker.inventory.index') }}" x-data="{ search: @js((string) request('search', '')) }">
            <input type="hidden" name="tab" value="fishTypes">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text"
                            name="search"
                            x-model="search"
                            placeholder="Search fish..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>
                <div class="filter-action-group">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishTypes']) }}"
                       class="btn-clear">
                        Clear
                    </a>
                    <button type="submit"
                            class="btn-search">
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Count -->
    @if($fishTypes->hasPages() || request()->has('search'))
        <div class="mb-4">
            <p class="text-sm text-gray-600">
                Showing {{ $fishTypes->firstItem() ?? 0 }} to {{ $fishTypes->lastItem() ?? 0 }} of {{ $fishTypes->total() }} fish
                @if(request()->has('search'))
                    <span class="text-green-600">(filtered)</span>
                @endif
            </p>
        </div>
    @endif

    <!-- Fish Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($fishTypes as $fishType)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                        <x-heroicon-o-tag class="w-5 h-5 text-white" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $fishType->display_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">{{ $fishType->display_description ?: 'No description provided yet.' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @unless($brokerViewReadOnly)
                                        <button type="button"
                                           class="text-green-600 hover:text-green-900 transition-colors"
                                           data-fish-type-edit-open
                                           data-fish-type-id="{{ $fishType->id }}"
                                           data-fish-type-name="{{ $fishType->display_name }}"
                                           data-fish-type-description="{{ $fishType->display_description }}">
                                            <x-heroicon-o-pencil-square class="w-6 h-6" />
                                        </button>
                                        @if($fishType->isUsed($brokerId ?? null))
                                            <button type="button" class="text-gray-400 cursor-not-allowed" title="Cannot delete: Fish is in use">
                                                <x-heroicon-o-trash class="w-6 h-6" />
                                            </button>
                                        @else
                                            <form action="{{ route('broker.fish-types.destroy', $fishType->id) }}" method="POST" class="inline" data-swal="delete" data-record-name="{{ $fishType->display_name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">
                                                    <x-heroicon-o-trash class="w-6 h-6" />
                                                </button>
                                            </form>
                                        @endif
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">No fish found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($fishTypes->hasPages())
        <div class="mt-8">
            {{ $fishTypes->appends(request()->query())->links('components.pagination') }}
        </div>
    @endif

    <div id="fish-type-edit-modal"
         class="fixed inset-0 z-[9999] hidden overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
            <button type="button"
                    class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]"
                    data-fish-type-edit-close
                    aria-label="Close edit fish"></button>
            <div class="relative z-10 w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-green-500 to-green-600 text-white shadow-sm">
                            <x-heroicon-o-tag class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-950">Edit Fish</h3>
                            <p class="mt-1 text-sm text-slate-500">Update the fish details for cleaner inventory setup.</p>
                        </div>
                    </div>
                    <button type="button"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            data-fish-type-edit-close
                            aria-label="Close edit fish">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <form method="POST" class="space-y-6 px-6 py-5" data-fish-type-edit-form>
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="fish-type-edit-name" class="block text-sm font-medium text-gray-700 mb-2">
                            Fish <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="fish-type-edit-name"
                               name="name"
                               required
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-green-500 focus:ring-2 focus:ring-green-500"
                               placeholder="Enter fish name">
                    </div>
                    <div>
                        <label for="fish-type-edit-description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea id="fish-type-edit-description"
                                  name="description"
                                  rows="4"
                                  class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                  placeholder="Add a short description"></textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <button type="button"
                                class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
                                data-fish-type-edit-close>
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto">
                            Update Fish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('fish-type-edit-modal');
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const form = modal?.querySelector('[data-fish-type-edit-form]');
            const nameInput = modal?.querySelector('#fish-type-edit-name');
            const descriptionInput = modal?.querySelector('#fish-type-edit-description');
            const updateUrlTemplate = @json($fishTypeUpdateUrlTemplate);

            const openModal = (button) => {
                if (!modal || !form || !nameInput || !descriptionInput) {
                    return;
                }

                form.action = updateUrlTemplate.replace('__ID__', encodeURIComponent(button.dataset.fishTypeId || ''));
                nameInput.value = button.dataset.fishTypeName || '';
                descriptionInput.value = button.dataset.fishTypeDescription || '';
                modal.classList.remove('hidden');
                document.documentElement.classList.add('modal-scroll-lock');
                document.body.classList.add('modal-scroll-lock');
                window.requestAnimationFrame(() => nameInput.focus({ preventScroll: true }));
            };

            const closeModal = () => {
                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');
                document.documentElement.classList.remove('modal-scroll-lock');
                document.body.classList.remove('modal-scroll-lock');
            };

            document.querySelectorAll('[data-fish-type-edit-open]').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            modal?.querySelectorAll('[data-fish-type-edit-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
</div>
