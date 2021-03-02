 <?php if (isset($component)) { $__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\AppLayout::class, []); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header'); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <?php echo e(__('Customers')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

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
                                    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <tr>
                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="flex items-center">

                                                <div class="">
                                                    <div class="text-sm leading-5 font-medium text-gray-900">
                                                        <?php echo e($customer->given_name); ?> <?php echo e($customer->family_name); ?>

                                                    </div>
                                                    <div class="text-sm leading-5 text-gray-500">
                                                        <?php echo e($customer->email); ?>

                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="text-sm leading-5 text-blue-900"> <a href="<?php echo e($customer->sim_customer_url); ?>" target="_blank"><?php echo e($customer->sim_id); ?> </a></div>

                                        </td>
                                        <!--  <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="text-sm leading-5 text-blue-900">

                                            <a href="<?php echo e('https://'. $customer->sim_url); ?>" target="_blank"><?php echo e($customer->sim_lead_id); ?> </a>

                                            </div>

                                        </td> -->

                                        <td class="px-6 py-4 whitespace-no-wrap">

                                            <?php if($customer->zoho_reference_id): ?>

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <a href="<?php echo e($customer->url); ?>" target="_blank"><?php echo e($customer->zoho_reference_id); ?> </a>
                                            </span>

                                            <?php else: ?>

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Not synced yet.
                                            </span>



                                            <?php endif; ?>

                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <?php if($customer->zoho_sub_id): ?>

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <a href="<?php echo e($customer->sub_url); ?>" target="_blank"><?php echo e($customer->zoho_sub_id); ?> </a>
                                            </span>

                                            <?php else: ?>

                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Not synced yet.
                                            </span>



                                            <?php endif; ?>

                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <div class="text-sm leading-5 text-gray-900 uppercase"><?php echo e($customer->customer_type); ?></div>

                                            <?php if($customer->customer_type =='lead'): ?>
                                            <div class="text-sm leading-5 text-gray-600">

                                                <a class="text-sm" href="<?php echo e('https://'. $customer->sim_url); ?>" target="_blank"><?php echo e($customer->sim_lead_id); ?> </a>

                                            </div>
                                            <?php endif; ?>
                                        </td>


                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <?php if(!$customer->archived): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                            <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Archived
                                            </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">

                                            <?php if( (!$customer->sim_id) && (!$customer->archived)): ?>
                                            <form method="POST" action="<?php echo e(route('pushToSimPro', $customer)); ?>">
                                                <?php echo csrf_field(); ?>

                                                <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center" onclick="event.preventDefault();this.closest('form').submit();">

                                                    SimPro <i class="fas fa-arrow-right"></i>




                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if( (!$customer->zoho_reference_id) && (!$customer->archived)): ?>
                                            <form method="POST" action="<?php echo e(route('pushToZoho', $customer)); ?>" id="<?php echo e('push'. $customer->id); ?>">
                                                <?php echo csrf_field(); ?>

                                                <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center" onclick="event.preventDefault();this.closest('form').submit();">

                                                    Zoho <i class="fas fa-arrow-right"></i>




                                                </button>
                                            </form>
                                            <?php endif; ?>


                                        </td>


                                    </tr>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-2 bg-gray-200 whitespace-no-wrap">
                                            <?php echo e(isset($data)?$data->links():''); ?>

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
 <?php if (isset($__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da)): ?>
<?php $component = $__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da; ?>
<?php unset($__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?> <?php /**PATH /home/493496.cloudwaysapps.com/zdhrxzffmg/public_html/resources/views/dashboard.blade.php ENDPATH**/ ?>