<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            @if($record->status === 'completed' && $record->final_path)
                <div class="bg-black rounded-lg overflow-hidden shadow-lg mb-4">
                    <video controls class="w-full aspect-video">
                        <source src="{{ route('recordings.play', $record) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            @else
                <div class="bg-gray-100 rounded-lg shadow p-6 flex flex-col items-center justify-center aspect-video text-gray-500 text-center mb-4 border-2 border-dashed border-gray-300">
                    <x-heroicon-o-video-camera class="w-12 h-12 mb-2 text-gray-400" />
                    <p class="font-medium">Video is not available</p>
                    <p class="text-sm mt-1">Status: {{ ucfirst($record->status) }}</p>
                    @if($record->status === 'failed')
                        <p class="text-sm mt-2 text-red-500 px-4 line-clamp-3">{{ $record->error_message }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
