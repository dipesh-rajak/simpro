<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Simpro Apps') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 ">
        <div class="md:grid md:grid-cols-7 md:gap-6">
            <div class="md:col-span-3 ">
                <div class="px-4 sm:px-0">

                    <h3 class="text-lg font-medium text-gray-900"> Simpro API Settings:</h3>
                    <p class="mt-1 text-gray-600 break-all">
                  

                    </p>

                    
                   
                </div>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-4">
                <div class="w-full max-w-7xl">

                    <form action="{{route('simpro.settings.post')}}" method="POST">
                        @csrf
                        <div class="shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-5 bg-white sm:p-6">
                                <div class="grid grid-cols-6 gap-6">


                                    <!-- Name -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="name">
                                            API Key
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full @error('api_key') border-red-500 @enderror" id="api_key" type="text" autocomplete="none" name="api_key" value="{{old('api_key', $settings['simpro.app.key']??config('services.simpro.api_key'))}}">
                                        @error('api_key')
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            {{ $message }}
                                           
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="col-span-6 sm:col-span-4 " id="gtRow">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Build URL
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full @error('url') border-red-500 @enderror" id="url" type="url" name="url" value="{{old('url', $settings['simpro.url']??config('services.simpro.client_url'))}}">

                                        @error('url')
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

   

</x-app-layout>