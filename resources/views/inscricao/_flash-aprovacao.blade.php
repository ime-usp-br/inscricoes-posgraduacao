@if (session('success'))
    <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200">{{ session('success') }}</div>
@endif
@if (session('info'))
    <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-blue-800 dark:text-blue-200">{{ session('info') }}</div>
@endif
@if ($errors->has('aprovacao'))
    <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200">{{ $errors->first('aprovacao') }}</div>
@endif
