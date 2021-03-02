<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Zoho Apps') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 ">
        <div class="md:grid md:grid-cols-7 md:gap-6">
            <div class="md:col-span-3 ">
                <div class="px-4 sm:px-0">

                    <h3 class="text-lg font-medium text-gray-900"> Required scopes:</h3>
                    <p class="mt-1 text-gray-600 break-all">
                    {{$scopes}}

                    </p>

                    <h3 class="text-lg font-medium text-gray-900"> Redirect URL:</h3>
                    <p class="mt-1 text-gray-600 break-all">

                    {{$redirect_url}}

                    </p>
                </div>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-4">
                <div class="w-full max-w-7xl">

                    <form action="{{route('authorizeZoho')}}" method="POST">
                        @csrf
                        <div class="shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-5 bg-white sm:p-6">
                                <div class="grid grid-cols-6 gap-6">


                                    <!-- Name -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="name">
                                            Client ID
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full @error('client_id') border-red-500 @enderror" id="client_id" type="text" autocomplete="none" name="client_id" value="{{old('client_id', $settings['zoho.app.id']??'')}}">
                                        @error('client_id')
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            {{ $message }}
                                           
                                        </span>
                                        @enderror
                                    </div>

                                    <!-- Secret -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Client Secret
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full @error('client_secret') border-red-500 @enderror " id="client_secret" type="password" name="client_secret" value="{{old('client_secret', $settings['zoho.app.secret']??'')}}">

                                        @error('client_secret')
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            {{ $message }}
                                          
                                        </span>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Email
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full @error('user_email') border-red-500 @enderror " id="user_email" type="email" name="user_email" value="{{old('user_email', $settings['zoho.app.user_email']??'')}}">

                                        @error('user_email')
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            {{ $message }}
                                          
                                        </span>
                                        @enderror
                                    </div>

                                    <!-- Self Client -->
                                    <div class="col-span-6 sm:col-span-4">

                                        <input class="mr-2 leading-tight" id="self_client" type="checkbox" name="self_client" {{old('self_client')? 'checked': ''}}>

                                        <span class="text-sm">Self Client?</span>
                                    </div>
                                    <!-- Secret -->
                                    <div class="col-span-6 sm:col-span-4 " id="gtRow">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Grant Token
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full @error('grant_token') border-red-500 @enderror" id="grant_token" type="password" name="grant_token" value="{{old('grant_token')}}">

                                        @error('grant_token')
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            {{ $message }}
                                           
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-start px-4 py-3 bg-gray-50 text-right sm:px-6">



                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150">
                                    Go
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {

            @if(old('self_client') =='on')
            $("#gtRow").show();
            @else
            $("#gtRow").hide();
            @endif
            $("#self_client").click(function() {
                $("#gtRow").toggle();
            })
        });
    </script>

</x-app-layout>