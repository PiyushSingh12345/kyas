<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <!-- <GuestLayout> -->
        <!-- <Head title="Log in" /> -->

        
		<div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>


      <section class="loginbg">
			<div class="container py-2 h-100">
				<div class="row d-flex justify-content-center align-items-center h-100">
					<div class="col col-xl-8">
						<div class="card" style="border-radius: 1rem; border: 1px solid #fff;">
							<div class="row g-0">
								<div class="col-md-6 col-lg-5 d-none d-md-block">
									<!-- <img src="assets/img/login_front.jpg" alt="login form" class="img-fluid" style="border-radius: 1rem 0 0 1rem;"> -->
									<img :src="'img/login_front.jpg'" alt="login form" class="img-fluid" style="border-radius: 1rem 0 0 1rem;">
								</div>
								<div class="col-md-6 col-lg-7 d-flex align-items-center">
									<div class="card-body p-4 p-lg-4 text-black">
										<div class="login-box text-center">
											
											<!-- Logo -->
										<img :src="'img/loginlogo.png'" alt="Logo" class="logo" style="margin-inline: auto;">
											<h4 class="mb-4">KY Automation System
											</h4>
											<!-- <form> -->
											<form @submit.prevent="submit">
												
												<!-- Email -->
												<div class="mb-3 text-start">
													<label class="form-label">User/ Email Id</label>
													<div class="input-group">
													<!--<span class="input-group-text">
															<i class="bi bi-envelope-fill"></i>
														</span> -->
														<!-- <input type="email" class="form-control" placeholder="name@example.com" required=""> -->
														 <TextInput
															id="email"
															type="email"
															class="mt-1 block w-full"
															v-model="form.email"
															required
															autofocus
															autocomplete="username"
														/>
														<InputError class="mt-2" :message="form.errors.email" />
													</div>
												</div>
												
												<!-- Password -->
												<div class="mb-1 text-start">
													<label class="form-label">Password</label>
													<div class="input-group">
												<!--<span class="input-group-text">
															<i class="bi bi-lock-fill"></i>
														</span> -->
														<!-- <input type="password" class="form-control" placeholder="Password" required=""> -->
														<TextInput
															id="password"
															type="password"
															class="mt-1 block w-full"
															v-model="form.password"
															required
															autocomplete="current-password"
														/>
														<InputError class="mt-2" :message="form.errors.password" />
													</div>
												</div>
												
												<!-- Forgot Password -->
												<!-- <div class="mb-3 d-flex justify-content-between">
													<a href="#" class="text-decoration-none small">
														<i class="bi bi-key"></i>
														Login Via OTP
													</a>
													<a href="#" class="text-decoration-none small text-end">
														<i class="bi bi-question-circle"></i>
														Forgot Password?
													</a>
												</div> -->
												
												<!-- OTP -->
												<!-- <div class="mb-3 text-start">
													<label class="form-label">OTP</label>
													<div class="input-group">
														<span class="input-group-text">
															<i class="bi bi-key-fill"></i>
														</span>
														<input type="text" class="form-control" placeholder="Enter OTP" required="">
														<button type="button" class="btn btn-outline-secondary">
															<i class="bi bi-arrow-repeat"></i>
															Resend OTP
														</button>
													</div>
												</div> -->
												
												<!-- Captcha -->
												<!-- <div class="mb-3 text-start">
													<label class="form-label">Enter Captcha</label>
													<div class="input-group">
														<span class="input-group-text">
															<i class="bi bi-shield-lock-fill"></i>
														</span>
														<input type="text" class="form-control" placeholder="Enter captcha" required="">
														<span class="captcha-img">X7K9P</span>
													</div>
												</div> -->
												
												<!-- Submit Button -->
												<div class="d-grid">
													<!-- <a href="dashboard.html" class="btn btn-grad w-90">
														SUBMIT
													</a> -->
													<div class="flex justify-center">
													<PrimaryButton
														class="loginsubmit"
														:class="{ 'opacity-25': form.processing }"
														:disabled="form.processing"
													>
														SUBMIT
													</PrimaryButton>
												</div>


												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
    <!-- </GuestLayout> -->
</template>
