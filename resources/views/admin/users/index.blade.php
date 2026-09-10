@extends('layouts.app')

@section('content')
<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">ユーザー管理</h1>
        <p class="mt-1 text-sm text-gray-500">ユーザーの検索とロール変更</p>
    </div>

    {{-- 検索フィルター --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">検索</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2.5 border" placeholder="名前・メールアドレス">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">ロール</label>
                <select name="role" id="role" class="rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2.5 border">
                    <option value="">すべて</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>admin</option>
                    <option value="coach" {{ request('role') === 'coach' ? 'selected' : '' }}>coach</option>
                    <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>student</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg px-4 py-2.5 shadow-sm transition-all duration-150">検索</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名前</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メール</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ロール</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">登録日</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->id }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-medium flex-shrink-0">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.users.updateRole', $user) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="text-sm rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-2 py-1.5 border">
                                        <option value="admin" {{ $user->isAdmin() ? 'selected' : '' }}>admin</option>
                                        <option value="coach" {{ $user->isCoach() ? 'selected' : '' }}>coach</option>
                                        <option value="student" {{ $user->isStudent() ? 'selected' : '' }}>student</option>
                                    </select>
                                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">変更</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('Y/m/d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        {{ $users->withQueryString()->links() }}
    </div>
</div>
@endsection
