<template id="createUr">
  <div class="wrapper">
    <Sidebar />

    <div class="main-panel">
      <Header />

      <div class="container">
        <div class="page-inner allinsideform">

          <div class="row">
            <div class="col-md-12">
              <div class="card">

                <div class="card-header">
                  <div class="card-title">Daily Sanction Module</div>
                </div>


                <div class="card-body">
                  <div class="row">


                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="email2">F.Y</label>
                        <select class="form-select" id="financialYear">
                          <option selected disabled>Select Financial Year</option>
                          <option value="2024-2025">2024–2025</option>
                          <option value="2023-2024">2023–2024</option>
                          <option value="2022-2023">2022–2023</option>
                          <option value="2021-2022">2021–2022</option>
                          <!-- Add more years as needed -->
                        </select>


                      </div>


                    </div>

                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="email2">State</label>
                        <select class="form-select" id="financialYear">
                          <option selected>-- Select State --</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="email2">DS Date</label>
                        <input type="date" class="form-control">
                      </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="email2">Mother Sanction</label>
                        <select class="form-select" id="financialYear">
                          <option selected>-- Select --</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="email2">IFD No.</label>
                        <input type="text" class="form-control" placeholder="7890" disabled>

                      </div>


                    </div>

                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="email2">SLS ID</label>
                        <input type="text" class="form-control" placeholder="1890" disabled>

                      </div>
                    </div>




                    <div class="table-responsive mt-5">
                      <table class="table table-bordered table-head-bg-primary">

                        <tbody>

                          <tr>

                            <td><label for="html" class="highlight_textbox">Budget Head</label></td>
                            <td><label for="html" class="highlight_textbox">Mother Sanctioned Amount</label></td>
                            <td><label for="html" class="highlight_textbox">Available MS Amount</label></td>
                            <td><label for="html" class="highlight_textbox">Center Share Amount</label></td>


                          </tr>








                          <tr>



                            <td> <select name="user" id="usertype" class="form-select">
                                <option selected>Auto Generated</option>
                              </select></td>

                            <td> <input type="text" class="form-control" placeholder="1890" disabled></td>

                            <td> <input type="text" class="form-control" placeholder="1890" disabled></td>

                            <td> <input type="text" class="form-control"></td>

                          </tr>



                          <tr>



                            <td> <select name="user" id="usertype" class="form-select">
                                <option selected>Auto Generated</option>
                              </select></td>

                            <td> <input type="text" class="form-control" placeholder="1890" disabled></td>

                            <td> <input type="text" class="form-control" placeholder="1890" disabled></td>

                            <td> <input type="text" class="form-control"></td>

                          </tr>


                        </tbody>
                      </table>
                    </div>

                  </div>
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
