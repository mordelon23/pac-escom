@if (session('estado'))
    <div role="status" class="mb-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
        <span aria-hidden="true">✓</span><span>{{ session('estado') }}</span>
    </div>
@endif
@if (session('error'))
    <div role="alert" class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <span aria-hidden="true">!</span><span>{{ session('error') }}</span>
    </div>
@endif
