<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    recaptcha: {
        type: Object,
        default: () => ({
            enabled: false,
            version: 'v2',
            siteKey: '',
        }),
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    captcha_token: '',
});

const isRecaptchaV3 = () => props.recaptcha?.version === 'v3';

const isRecaptchaV2 = () => props.recaptcha?.version === 'v2';

const loadRecaptchaScript = () => {
    if (!props.recaptcha?.enabled || !props.recaptcha?.siteKey) {
        return;
    }

    const scriptSrc = isRecaptchaV3()
        ? `https://www.google.com/recaptcha/api.js?render=${props.recaptcha.siteKey}`
        : 'https://www.google.com/recaptcha/api.js';
    const existingScript = document.querySelector(`script[src="${scriptSrc}"]`);

    if (!existingScript) {
        const script = document.createElement('script');
        script.src = scriptSrc;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
};

const renderRecaptchaV2Widget = () => {
    if (!props.recaptcha?.enabled || !isRecaptchaV2()) {
        return false;
    }

    if (!window.grecaptcha) {
        return false;
    }

    const container = document.getElementById('recaptcha-container');
    if (!container || container.childElementCount > 0) {
        return false;
    }

    window.grecaptcha.render(container, {
        sitekey: props.recaptcha.siteKey,
    });

    return true;
};

onMounted(() => {
    loadRecaptchaScript();

    if (props.recaptcha?.enabled && isRecaptchaV2()) {
        const intervalId = window.setInterval(() => {
            if (renderRecaptchaV2Widget()) {
                window.clearInterval(intervalId);
            }
        }, 150);

        window.setTimeout(() => {
            window.clearInterval(intervalId);
        }, 6000);
    }
});

const getCaptchaToken = async () => {
    if (!props.recaptcha?.enabled || !props.recaptcha?.siteKey) {
        return '';
    }

    if (!window.grecaptcha) {
        throw new Error('reCAPTCHA is not loaded');
    }

    if (isRecaptchaV2()) {
        const token = window.grecaptcha.getResponse();
        if (!token) {
            throw new Error('Please complete the CAPTCHA challenge');
        }

        return token;
    }

    return new Promise((resolve) => {
        window.grecaptcha.ready(async () => {
            const token = await window.grecaptcha.execute(props.recaptcha.siteKey, { action: 'login' });
            resolve(token);
        });
    });
};

const submit = async () => {
    if (props.recaptcha?.enabled) {
        try {
            form.captcha_token = await getCaptchaToken();
        } catch (error) {
            form.setError('email', error?.message || 'Captcha verification failed. Please refresh and try again.');
            return;
        }
    }

    form.post(route('login'), {
        onFinish: () => form.reset('password', 'captcha_token'),
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
														<!-- <span class="input-group-text">
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
														<!-- <span class="input-group-text">
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
                                                <div v-if="recaptcha?.enabled && recaptcha?.version === 'v2'" class="mb-3 text-start">
                                                    <div id="recaptcha-container"></div>
                                                </div>
												
												<!-- Submit Button -->
												<!-- <div class="d-grid"> -->
												<div>
													<!-- <a href="dashboard.html" class="btn btn-grad w-90">
														SUBMIT
													</a> -->
													<PrimaryButton
														class="loginsubmit"
														:class="{ 'opacity-25': form.processing }"
														:disabled="form.processing"
													>
														SUBMIT
													</PrimaryButton>
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
