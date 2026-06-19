@php
    /** @var array<string, mixed> $record */
    $record = $getRecord();
@endphp

<pre class="overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-4 font-mono text-xs leading-relaxed text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $record['body'] }}</pre>
