@extends("app")
@section("title", "Login - Agenda")

@section("content")
<section class="bg-base-100">
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
        <div class="flex items-center mb-6">
            <img class="w-[280px] mb-5" src="/images/logo.png" alt="logo">
        </div>
        <div class="w-full shadow dark:border md:mt-0 sm:max-w-md xl:p-0 bg-secondary">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <form action="{{ route('auth.index') }}" method="post" class="">
                    @csrf
                    <div class="mb-5">
                        <label for="email" class="block mb-2 text-sm font-medium text-white dark:text-white">Email</label>
                        <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 sm:text-sm focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="" required="" value="{{ old('email') }}">
                    </div>
                    <div class="mb-5">
                        <label for="password" class="block mb-2 text-sm font-medium text-white dark:text-white">Mot de passe</label>
                        <input type="password" name="password" id="password" placeholder="" class="bg-gray-50 border border-gray-300 sm:text-sm focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                    </div>
                    @error('errorsCredentials')
                    <div class="bg-red-100 text-red-700 text-center font-bold p-5 w-full mb-5 rounded-md">
                        {{ $message }}
                    </div>
                    @enderror
                    <button type="submit" class="w-full btn btn-primary text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">SE CONNECTER</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
