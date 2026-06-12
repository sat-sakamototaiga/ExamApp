<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="/">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        @php($user = Auth::user())

                        <x-nav-link :href="route('quiz.select_exam')" :active="request()->routeIs('quiz.*')">
                            試験開始
                        </x-nav-link>

                        @if ($user->isStudent())
                            <x-nav-link :href="route('dashboard.feedback-history')" :active="request()->routeIs('dashboard.feedback-history')">
                                FB履歴
                            </x-nav-link>
                        @endif

                        @if ($user->hasRoleLevel(\App\Models\User::ROLE_TEACHER))
                            <x-nav-link :href="route('questions.index')" :active="request()->routeIs('questions.*')">
                                問題管理
                            </x-nav-link>
                            <x-nav-link :href="route('exams.index')" :active="request()->routeIs('exams.*')">
                                試験管理
                            </x-nav-link>
                        @endif

                        @if ($user->isTeacher())
                            <x-nav-link :href="route('teacher.students.progress')" :active="request()->routeIs('teacher.students.*')">
                                担当生徒の進捗
                            </x-nav-link>
                        @endif

                        @if ($user->isAdmin())
                            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">
                                ユーザー管理
                            </x-nav-link>
                        @endif
                    @else
                        <x-nav-link :href="route('login')" :active="request()->routeIs('login')">
                            ログイン
                        </x-nav-link>
                        @if (Route::has('register'))
                            <x-nav-link :href="route('register')" :active="request()->routeIs('register')">
                                新規登録
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                プロフィール
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('quiz.select_exam')">
                                試験開始
                            </x-dropdown-link>
                            @if (Auth::user()->hasRoleLevel(\App\Models\User::ROLE_TEACHER))
                                <x-dropdown-link :href="route('questions.index')">
                                    問題管理
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('teacher.students.progress')">
                                    担当生徒の進捗
                                </x-dropdown-link>
                            @endif
                            @if (Auth::user()->isAdmin())
                                <x-dropdown-link :href="route('admin.users.index')">
                                    ユーザー管理
                                </x-dropdown-link>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    ログアウト
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                @php($user = Auth::user())

                <x-responsive-nav-link :href="route('quiz.select_exam')" :active="request()->routeIs('quiz.*')">
                    試験開始
                </x-responsive-nav-link>

                @if ($user->isStudent())
                    <x-responsive-nav-link :href="route('dashboard.feedback-history')" :active="request()->routeIs('dashboard.feedback-history')">
                        FB履歴
                    </x-responsive-nav-link>
                @endif

                @if ($user->hasRoleLevel(\App\Models\User::ROLE_TEACHER))
                    <x-responsive-nav-link :href="route('questions.index')" :active="request()->routeIs('questions.*')">
                        問題管理
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('exams.index')" :active="request()->routeIs('exams.*')">
                        試験管理
                    </x-responsive-nav-link>
                @endif

                @if ($user->isTeacher())
                    <x-responsive-nav-link :href="route('teacher.students.progress')" :active="request()->routeIs('teacher.students.*')">
                        担当生徒の進捗
                    </x-responsive-nav-link>
                @endif

                @if ($user->isAdmin())
                    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">
                        ユーザー管理
                    </x-responsive-nav-link>
                @endif
            @else
                <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                    ログイン
                </x-responsive-nav-link>
                @if (Route::has('register'))
                    <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                        新規登録
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('quiz.select_exam')">
                        試験開始
                    </x-responsive-nav-link>
                    @if (Auth::user()->hasRoleLevel(\App\Models\User::ROLE_TEACHER))
                        <x-responsive-nav-link :href="route('questions.index')">
                            問題管理
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teacher.students.progress')">
                            担当生徒の進捗
                        </x-responsive-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <x-responsive-nav-link :href="route('admin.users.index')">
                            ユーザー管理
                        </x-responsive-nav-link>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
