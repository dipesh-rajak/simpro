 <?php if (isset($component)) { $__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\AppLayout::class, []); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header'); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <?php echo e(__('Zoho Apps')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 ">
        <div class="md:grid md:grid-cols-7 md:gap-6">
            <div class="md:col-span-3 ">
                <div class="px-4 sm:px-0">

                    <h3 class="text-lg font-medium text-gray-900"> Required scopes:</h3>
                    <p class="mt-1 text-gray-600 break-all">
                    <?php echo e($scopes); ?>


                    </p>

                    <h3 class="text-lg font-medium text-gray-900"> Redirect URL:</h3>
                    <p class="mt-1 text-gray-600 break-all">

                    <?php echo e($redirect_url); ?>


                    </p>
                </div>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-4">
                <div class="w-full max-w-7xl">

                    <form action="<?php echo e(route('authorizeZoho')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-5 bg-white sm:p-6">
                                <div class="grid grid-cols-6 gap-6">


                                    <!-- Name -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="name">
                                            Client ID
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full <?php $__errorArgs = ['client_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="client_id" type="text" autocomplete="none" name="client_id" value="<?php echo e(old('client_id', $settings['zoho.app.id']??'')); ?>">
                                        <?php $__errorArgs = ['client_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            <?php echo e($message); ?>

                                           
                                        </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Secret -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Client Secret
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full <?php $__errorArgs = ['client_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> " id="client_secret" type="password" name="client_secret" value="<?php echo e(old('client_secret', $settings['zoho.app.secret']??'')); ?>">

                                        <?php $__errorArgs = ['client_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            <?php echo e($message); ?>

                                          
                                        </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Email
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full <?php $__errorArgs = ['user_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> " id="user_email" type="email" name="user_email" value="<?php echo e(old('user_email', $settings['zoho.app.user_email']??'')); ?>">

                                        <?php $__errorArgs = ['user_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            <?php echo e($message); ?>

                                          
                                        </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Self Client -->
                                    <div class="col-span-6 sm:col-span-4">

                                        <input class="mr-2 leading-tight" id="self_client" type="checkbox" name="self_client" <?php echo e(old('self_client')? 'checked': ''); ?>>

                                        <span class="text-sm">Self Client?</span>
                                    </div>
                                    <!-- Secret -->
                                    <div class="col-span-6 sm:col-span-4 " id="gtRow">
                                        <label class="block font-medium text-sm text-gray-700" for="email">
                                            Grant Token
                                        </label>

                                        <input class="form-input rounded-md shadow-sm mt-1 block w-full <?php $__errorArgs = ['grant_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="grant_token" type="password" name="grant_token" value="<?php echo e(old('grant_token')); ?>">

                                        <?php $__errorArgs = ['grant_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="flex items-center font-medium tracking-wide text-red-500 text-xs mt-1 ml-1">
                                            <?php echo e($message); ?>

                                           
                                        </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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

            <?php if(old('self_client') =='on'): ?>
            $("#gtRow").show();
            <?php else: ?>
            $("#gtRow").hide();
            <?php endif; ?>
            $("#self_client").click(function() {
                $("#gtRow").toggle();
            })
        });
    </script>

 <?php if (isset($__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da)): ?>
<?php $component = $__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da; ?>
<?php unset($__componentOriginal8e2ce59650f81721f93fef32250174d77c3531da); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?> <?php /**PATH /home/371465.cloudwaysapps.com/msruyuytpk/public_html/resources/views/zoho/init.blade.php ENDPATH**/ ?>