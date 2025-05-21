<template>
  <!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>krishi Unnati</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport">
	<link rel="icon" href="assets/img/favi.png" type="image/x-icon">
	
	<!-- G Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Beiruti:wght@200..900&amp;display=swap" rel="stylesheet">

	<!-- CSS Files -->
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/plugins.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/kaiadmin.min.css">

	<!-- CSS Just for demo purpose, don't include it in your project -->
	<link rel="stylesheet" href="assets/css/demo.css">
</head>

<body >
  <div class="wrapper">
    <Sidebar />

    <div class="main-panel">
      <Header />

      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">User Management</h3>
            <!-- Breadcrumbs -->
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Dashboard</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Create User</a></li>
            </ul>
          </div>

          <!-- Dashboard cards -->
          <div class="row mt-3">
            <div class="col-sm-6 col-md-4" v-for="card in statsCards" :key="card.title">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div :class="`icon-big text-center ${card.iconColor} bubble-shadow-small`">
                        <i :class="card.icon"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">{{ card.title }}</p>
                        <h4 class="card-title">{{ card.count }}</h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Create User Form -->
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Create User</div>
                </div>

             
                <div class="card-body">
									<div class="row">
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">First Name</label>
												<input type="email" class="form-control" id="email2"
													placeholder="Your Name">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">Last Name</label>
												<input type="email" class="form-control" id="email2"
													placeholder="Last Name">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">Designation</label>
												<input type="email" class="form-control" id="email2"
													placeholder="Enter Designation">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">Mobile No.</label>
												<input type="number" class="form-control" id="email2"
													placeholder="Enter Mobile">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">Email id.</label>
												<input type="email" class="form-control" id="email2"
													placeholder="Enter id">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">Program Division</label>
												<input type="email" class="form-control" id="email2"
													placeholder="program division">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email2">User Type</label>
												<select name="user" id="usertype" class="form-select">
													<option value="0">--- Select ---</option>
													<option value="1">Master Data</option>
													<option value="2">Admin</option>
													<option value="mercedes">KY/PD</option>
													<option value="mercedes">KY/PD</option>
												</select>
											</div>
										</div>
									</div>
								</div>


                <div class="card-body">
                  <div class="row">
                     <!-- <div
                      class="col-md-6 col-lg-4"
                      v-for="(value, key) in form"
                      :key="key"
                      v-if="key !== 'user_type'"
                    >
                      <div class="form-group">
                        <label :for="key">{{ key.replace('_', ' ').toUpperCase() }}</label>
                        <input
                          v-model="form[key]"
                          :id="key"
                          type="text"
                          class="form-control"
                          :placeholder="`Enter ${key.replace('_', ' ')}`"
                        />
                      </div>
                    </div>  -->

                    <!-- User Type dropdown -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select v-model="form.user_type" id="user_type" class="form-select">
                          <option value="">--- Select ---</option>
                          <option value="master">Master Data</option>
                          <option value="admin">Admin</option>
                          <option value="ky_pd">KY/PD</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-action">
                  <button class="btn btn-primary" @click="submitForm">Submit</button>
                  <button class="btn btn-danger" type="button">Cancel</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  </div>

  </body>

</html>
</template>

  <script setup>
    import { useForm } from '@inertiajs/vue3'
    import { onMounted } from 'vue'
    import { useScriptTag } from '@vueuse/core'

    // Correct relative paths (from createUser.vue to Common/)
    import Header from './Common/Header.vue'
    import Sidebar from './Common/Sidebar.vue'
    import Footer from './Common/Footer.vue'

    // Form state
    const form = useForm({
      first_name: '',
      last_name: '',
      designation: '',
      mobile: '',
      email: '',
      program_division: '',
      user_type: '',
    })

    // Submit handler
    const submitForm = () => {
      form.post('/users') // Update this to your actual route
    }

    // Dashboard card data
    const statsCards = [
      {
        title: 'Total User',
        count: '1,294',
        icon: 'fas fa-users',
        iconColor: 'icon-primary',
      },
      {
        title: 'Total PD Users',
        count: '1,303',
        icon: 'fas fa-user-check',
        iconColor: 'icon-info',
      },
      {
        title: 'Total KY Division Users',
        count: '1,345',
        icon: 'fas fa-luggage-cart',
        iconColor: 'icon-success',
      },
    ]


    //  Core JS Files
	// useScriptTag("assets/js/core/jquery-3.7.1.min.js");
	useScriptTag("assets/js/core/jquery-3.7.1.min.js");
	useScriptTag("assets/js/core/popper.min.js");
	useScriptTag("assets/js/core/bootstrap.min.js");

	// jQuery Scrollbar
	useScriptTag("assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js");


	// Kaiadmin JS
	useScriptTag("assets/js/kaiadmin.min.js");

	// Kaiadmin DEMO methods, don't include it in your project!
	useScriptTag("assets/js/setting-demo.js");
	useScriptTag("assets/js/demo.js");

    useScriptTag('assets//js/plugin/webfont/webfont.min.js', () => {
      if (window.WebFont) {
        window.WebFont.load({
          google: {
            families: ['Public Sans:300,400,500,600,700'],
          },
          custom: {
            families: [
              'Font Awesome 5 Solid',
              'Font Awesome 5 Regular',
              'Font Awesome 5 Brands',
              'simple-line-icons',
            ],
            urls: ['/assets/css/fonts.min.css'],
          },
          active() {
            sessionStorage.fonts = true;
          },
        });
      }
    });


  </script>
