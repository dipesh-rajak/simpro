<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Customers') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Simpro Customer ID
                                        </th>
                                        <!--  <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Simpro Lead ID
                                        </th> -->
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Zoho CRM ID
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Zoho Sub ID
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>

                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">

                                        </th>

                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($data as $customer)

                                    <tr>
                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="flex items-center">

                                                <div class="">
                                                    <div class="text-sm leading-5 font-medium text-gray-900">
                                                        {{$customer->given_name}} {{$customer->family_name}}
                                                    </div>
                                                    <div class="text-sm leading-5 text-gray-500">
                                                        {{$customer->email}}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="text-sm leading-5 text-blue-900"> <a href="{{ $customer->sim_customer_url }}" target="_blank">{{$customer->sim_id}} </a></div>

                                        </td>
                                        <!--  <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="text-sm leading-5 text-blue-900">

                                            <a href="{{ 'https://'. $customer->sim_url }}" target="_blank">{{$customer->sim_lead_id}} </a>

                                            </div>

                                        </td> -->

                                        <td class="px-6 py-4 whitespace-no-wrap">

                                            @if($customer->zoho_reference_id)

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <a href="{{ $customer->url }}" target="_blank">{{$customer->zoho_reference_id}} </a>
                                            </span>

                                            @else

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Not synced yet.
                                            </span>



                                            @endif

                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            @if($customer->zoho_sub_id)

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <a href="{{ $customer->sub_url }}" target="_blank">{{$customer->zoho_sub_id}} </a>
                                            </span>

                                            @else

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Not synced yet.
                                            </span>



                                            @endif

                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="text-sm leading-5 text-gray-900 uppercase">{{$customer->customer_type}}</div>

                                            @if($customer->customer_type =='lead')
                                            <div class="text-sm leading-5 text-gray-600">

                                                <a class="text-sm" href="{{ 'https://'. $customer->sim_url }}" target="_blank">{{$customer->sim_lead_id}} </a>

                                            </div>
                                            @endif
                                        </td>


                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            @if(!$customer->archived)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                            @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Archived
                                            </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">

                                            @if ( (!$customer->sim_id) && (!$customer->archived))
                                            <form method="POST" action="{{route('pushToSimPro', $customer)}}">
                                                @csrf

                                                <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center" onclick="event.preventDefault();this.closest('form').submit();">

                                                    SimPro <i class="fas fa-arrow-right"></i>




                                                </button>
                                            </form>
                                            @endif
                                            @if ( (!$customer->zoho_reference_id) && (!$customer->archived))
                                            <form method="POST" action="{{route('pushToZoho', $customer)}}" id="{{'push'. $customer->id}}">
                                                @csrf

                                                <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center" onclick="event.preventDefault();this.closest('form').submit();">

                                                    Zoho <i class="fas fa-arrow-right"></i>




                                                </button>
                                            </form>
                                            @endif


                                        </td>


                                    </tr>

                                    @endforeach
                                    <tr>
                                        <td colspan="8" class="px-6 py-2 bg-gray-200 whitespace-no-wrap">
                                            {{isset($data)?$data->links():''}}
                                        </td>
                                    </tr>
                                    <!-- More rows... -->
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>