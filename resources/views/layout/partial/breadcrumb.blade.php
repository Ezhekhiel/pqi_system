<nav class="mt-4" id="display_breadcrumb">
    @php
        // Ambil segment URL, misalnya:
        // /dashboard/users/list → ['dashboard', 'users', 'list']
        $segments = request()->segments();
    @endphp

    <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">

        {{-- HOME / PAGES --}}
        <li class="text-sm leading-normal">
            <a class="opacity-50 text-slate-700" href="{{ url('/') }}">Home</a>
        </li>

        {{-- LOOP SEGMENT --}}
        @foreach ($segments as $index => $segment)
            <li class="text-sm pl-2 capitalize leading-normal text-slate-700
                    before:float-left before:pr-2 before:text-gray-600 before:content-['/']"
                aria-current="page">

                {{-- Jika bukan segment terakhir → klikable --}}
                @if ($index !== count($segments) - 1)
                    <a href="{{ url(implode('/', array_slice($segments, 0, $index + 1))) }}" class="text-slate-700">
                        {{ str_replace('-', ' ', $segment) }}
                    </a>
                @else
                    {{-- Segment terakhir (active) --}}
                    <span class="font-semibold text-slate-900">
                        {{ str_replace('-', ' ', $segment) }}
                    </span>
                @endif

            </li>
        @endforeach
    </ol>
</nav>
