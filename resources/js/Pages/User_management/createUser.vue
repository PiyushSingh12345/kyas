<template id="createUr">
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
              <li class="nav-item"><a href="/user-listing">Dashboard</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="/user-create">Create User</a></li>
            </ul>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <a href="/user-listing" class="btn btn-primary float-right">Edit User</a>
          </div>
          <div class="page-message">
            <h2 class="showmsg text-success"></h2>
            <h2 class="showerror text-danger"></h2>
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
												<label for="first_name">First Name</label>
												<input type="text" class="form-control" name="first_name" v-model="form.first_name" id="first_name"
													placeholder="First Name">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="last_name">Last Name</label>
												<input type="text" class="form-control" name="last_name" v-model="form.last_name" id="last_name"
													placeholder="Last Name">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="designation">Designation</label>
												<input type="text" class="form-control" name="designation" v-model="form.designation" id="designation"
													placeholder="Enter Designation">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="mobile">Mobile No.</label>
												<input type="number" class="form-control" name="mobile" v-model="form.mobile" id="mobile"
													placeholder="Enter Mobile">
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="email">Email id.</label>
												<input type="email" class="form-control" name="email" v-model="form.email" id="email"
													placeholder="Enter Email Id">
											</div>
										</div>
                    <!-- change the code, on select of ky_division in program division the drop down values of user type will be filtered and shown ky related values like KY User and KY_Admin -->



                    <div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="program_division">Program Division</label>
												<select name="program_division" id="program_division" class="form-select" v-model="form.program_division">
													<option value="">--- Select ---</option>
													<option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">
														{{ division.division_name }}
													</option>
												</select>
											</div>
										</div>
										<div class="col-md-6 col-lg-4">
											<div class="form-group">
												<label for="user_type">User Type</label>
                        <!-- {{ userTypes }} -->
                        <!-- Using v-model to bind the selected user types , multiple selection enabled data coming from md_user_types table -->
                        <!-- <select name="user_type" id="user_type" class="form-select" v-model="form.user_type" multiple> -->
                            <!-- <option value="">--- Select ---</option> -->
                            <!-- <option v-for="userType in userTypes" :key="userType.md_user_type_id" :value="userType.md_user_type_id"> -->
                              <!-- <option v-for="userType in filteredUserTypes" :key="userType.md_user_type_id" :value="userType.md_user_type_id"> -->
                                <!-- {{ userType.user_type_name }} -->
                            <!-- </option> -->
                        <!-- </select> -->
                        <select name="user_type" id="user_type" class="form-select" v-model="form.user_type" multiple>
                          <option value="">--- Select ---</option>
                          <option v-for="userType in filteredUserTypes" :key="userType.md_user_type_id" :value="userType.md_user_type_id">
                            {{ userType.user_type_name }}
                          </option>
                        </select>
											</div>
										</div>
									</div>
								</div>

                <div class="card-action">
                  <button class="btn btn-primary" @click="submitForm">Submit</button>
                  <!-- <button class="btn btn-primary" >Submit</button> -->
                  <button class="btn btn-danger" type="button"  @click="resetForm" >Cancel</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  </div>

</template>

  <script setup>
    import { useForm } from '@inertiajs/vue3'
    import { onMounted, ref, computed, watch } from 'vue'
    import axios from 'axios'
    import { useScriptTag } from '@vueuse/core'

    // Correct relative paths (from createUser.vue to Common/)
    import Header from '../Common/Header.vue'
    import Sidebar from '../Common/Sidebar.vue'
    import Footer from '../Common/Footer.vue'

    // Form state
    const form = useForm({
      first_name: '',
      last_name: '',
      designation: '',
      mobile: '',
      email: '',
      program_division: '',
      user_type: [],
    })



    const filteredUserTypes = computed(() => {
      // If program_division is '1', filter user types to only include KY_Admin and KY User
      const KY_TYPE_IDS = [1, 2];
      const selectedDivision = parseInt(form.program_division);

      if (!selectedDivision) {
        // No division selected: show all types
        return userTypes.value;
      }

      if (selectedDivision === 1) {
        // KY Division selected: show only KY types
        return userTypes.value.filter(type =>
          KY_TYPE_IDS.includes(parseInt(type.md_user_type_id))
        );
      }

      // Other divisions selected: show all except KY types
      return userTypes.value.filter(type =>
        !KY_TYPE_IDS.includes(parseInt(type.md_user_type_id))
      );      
    })

    watch(() => form.program_division, () => {
      form.user_type = [];
    });

    const resetForm = () => {
      form.reset();
    };

    // Submit handler
    const submitForm = () => {
      form.post('/users',
        {
          onSuccess: () => {
            // Reset form after successful submission
            form.reset();
            
            // Success message in the showmsg class of h2 tag in the page-message class div for 3 ms only
            // this.$toast.success('User created successfully!');
            const messageElement = document.querySelector('.showmsg');
            messageElement.textContent = 'User created successfully!';
            setTimeout(() => {
              messageElement.textContent = '';
            }, 3000);
            
          },
          onError: (errors) => {
            console.error('Form submission errors:', errors)
            // can show an error message on screen
            // this.$toast.error('Failed to create user. ' + errors.join(', '));
            const errorElement = document.querySelector('.showerror');
            errorElement.textContent = 'Failed to create user. ' + errors.join(', ');
            setTimeout(() => {
              errorElement.textContent = '';
            }, 3000);
          },
        }) // Update this to your actual route
    }

    // This will hold dropdown options
    const programDivisions = ref([])
    const userTypes = ref([])

    // Fetch divisions from server
    onMounted(async () => {
      try {
        const response = await axios.get('/md-program-divisions')
        programDivisions.value = response.data
      } catch (error) {
        console.error('Failed to fetch program divisions', error)
      }

      try {
        const responseMUT = await axios.get('/md-user-types')
        userTypes.value = responseMUT.data
      } catch (error) {
        console.error('Failed to fetch user types', error)
      }
    })
    

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
  // useScriptTag("assets/js/core/jquery-3.7.1.min.js");
  // useScriptTag("assets/js/core/popper.min.js");
  // useScriptTag("assets/js/core/bootstrap.min.js");

  // // jQuery Scrollbar
  // useScriptTag("assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js");


  // // Kaiadmin JS
  // useScriptTag("assets/js/kaiadmin.min.js");

  // // Kaiadmin DEMO methods, don't include it in your project!
  // useScriptTag("assets/js/setting-demo.js");
  // useScriptTag("assets/js/demo.js");

    


  </script>
