<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Leave Approval</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 px-4 py-10">
    <main class="mx-auto max-w-lg">
        <section
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        >
            <div
                class="bg-gradient-to-r from-indigo-700 to-indigo-500 px-6 py-7 text-white"
            >
                <p
                    class="text-xs font-semibold uppercase tracking-widest text-indigo-100"
                >
                    Pratibha Pratiksha
                </p>

                <h1 class="mt-2 text-2xl font-bold">
                    Leave Approval Request
                </h1>
            </div>

            <div class="space-y-5 p-6">
                @if(session('success'))
                    <div
                        class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                    >
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div
                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('message'))
                    <div
                        class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700"
                    >
                        {{ session('message') }}
                    </div>
                @endif

                <div>
                    <p class="text-xs text-slate-400">
                        Resident
                    </p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        {{ trim(
                            $leave->resident->first_name
                            . ' '
                            . $leave->resident->last_name
                        ) }}
                    </p>

                    <p class="text-xs text-slate-500">
                        {{ $leave->resident->resident_code }}
                    </p>
                </div>

                <dl
                    class="grid grid-cols-2 gap-4 rounded-xl bg-slate-50 p-4 text-sm"
                >
                    <div>
                        <dt class="text-xs text-slate-400">
                            Leave Type
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $leave->leave_type_label }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-slate-400">
                            Duration
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $leave->total_days }}
                            day{{ $leave->total_days === 1 ? '' : 's' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-slate-400">
                            From
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $leave->from_date->format('d-m-Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-slate-400">
                            To
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $leave->to_date->format('d-m-Y') }}
                        </dd>
                    </div>

                    <div class="col-span-2">
                        <dt class="text-xs text-slate-400">
                            Destination
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-800">
                            {{ $leave->destination ?: 'Not provided' }}
                        </dd>
                    </div>

                    <div class="col-span-2">
                        <dt class="text-xs text-slate-400">
                            Reason
                        </dt>

                        <dd class="mt-1 whitespace-pre-line text-slate-700">
                            {{ $leave->reason }}
                        </dd>
                    </div>
                </dl>

                @if(
                    $leave->parent_approval_status === 'pending'
                    && !in_array(
                        $leave->final_status,
                        ['cancelled', 'expired']
                    )
                )
                    <form
                        method="POST"
                        action="{{ route(
                            'leave.parent.respond',
                            $token
                        ) }}"
                        class="space-y-4"
                    >
                        @csrf

                        <div>
                            <label
                                for="remarks"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Remarks, if any
                            </label>

                            <textarea
                                id="remarks"
                                name="remarks"
                                rows="3"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            >{{ old('remarks') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="submit"
                                name="action"
                                value="reject"
                                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"
                            >
                                Reject
                            </button>

                            <button
                                type="submit"
                                name="action"
                                value="approve"
                                class="rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white"
                            >
                                Approve
                            </button>
                        </div>
                    </form>
                @else
                    <div
                        class="rounded-xl border px-4 py-4 text-center"
                        @class([
                            'border-green-200 bg-green-50 text-green-700'
                                => $leave->parent_approval_status === 'approved',

                            'border-red-200 bg-red-50 text-red-700'
                                => $leave->parent_approval_status === 'rejected',

                            'border-slate-200 bg-slate-50 text-slate-600'
                                => !in_array(
                                    $leave->parent_approval_status,
                                    ['approved', 'rejected']
                                ),
                        ])
                    >
                        <p class="text-sm font-bold">
                            Status:
                            {{ ucfirst(
                                $leave->parent_approval_status
                            ) }}
                        </p>

                        @if($leave->parent_responded_at)
                            <p class="mt-1 text-xs">
                                Responded on
                                {{ $leave->parent_responded_at
                                    ->format('d-m-Y h:i A') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>