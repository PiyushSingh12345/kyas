<template id="editUr">
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
                  <div class="card-title">Update/Delete User</div>
                </div>

             
                <div class="card-body">
                    <div class="table-responsive">
											<table class="table table-bordered table-head-bg-primary">
												<thead>
													<tr>
														<th>#</th>
														<th>First Name</th>
														<th>Last Name</th>
														<th>Designation</th>
														<th>Mobile No.</th>
														<th>Email id.</th>
														<th>Program Division</th>
														<th>Password</th>
														<th>User Type</th>
														<th>Action</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<th scope="row">1</th>
														<td>Akash kumar</td>
														<td>Rana</td>
														<td>US IT</td>
														<td>xyx@gmail.com</td>
														<td>Table cell</td>
														<td>xyxcccc</td>
														<td>*******</td>
														<td>Admin</td>
														<td>
															<a href="#" class="me-2" data-bs-toggle="modal" data-bs-target="#myModal">
																<i class="fas fa-edit"></i>
															</a>
															<a href="#" class="me-2" data-bs-toggle="modal" data-bs-target="#myModalDel">
																<i class="fas fa-trash"></i>
															</a>
														</td>
													</tr>
													<tr>
														<th scope="row">2</th>
														<td>Menu kumar</td>
														<td>Rana</td>
														<td>US IT</td>
														<td>xyx@gmail.com</td>
														<td>xyz</td>
														<td>xyxcccc</td>
														<td>*******</td>
														<td>Master Data</td>
														<td>
															<a href="#" class="me-2" data-bs-toggle="modal" data-bs-target="#myModal">
																<i class="fas fa-edit"></i>
															</a>
															<a href="#" class="me-2" data-bs-toggle="modal" data-bs-target="#myModalDel">
																<i class="fas fa-trash"></i>
															</a>
														</td>
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

      <Footer />
    </div>
  </div>

  <!-- The Modal -->
  <div class="modal" id="myModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">Update User</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <!-- Modal body -->
        <div class="modal-body">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email2">First Name</label>
                  <input type="email" class="form-control" id="email2" placeholder="Akash Kumar">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email2">Last Name</label>
                  <input type="email" class="form-control" id="email2" placeholder="Rana">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email2">Designation</label>
                  <input type="email" class="form-control" id="email2" placeholder="KY Admin">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email2">Mobile No.</label>
                  <input type="email" class="form-control" id="email2" placeholder="9999999999">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email2">Email id.</label>
                  <input type="email" class="form-control" id="email2" placeholder="xyz@gmail.com">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="program_division">Program Division</label>
                  <select name="program_division" id="program_division"  class="form-select" v-model="form.program_division">
                    <option value="">--- Select ---</option>
                    <option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">
                     
                      {{ division.division_name }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email2">Password</label>
                  <input type="password" class="form-control" placeholder="**********">
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="user_type">User Type</label>
                  <!-- {{ userTypes }} -->
                  <!-- Using v-model to bind the selected user types , multiple selection enabled data coming from md_user_types table -->
                  <select name="user_type" id="user_type" class="form-select" v-model="form.user_type" multiple>
                      <option value="">--- Select ---</option>
                      <option v-for="userType in userTypes" :key="userType.md_user_type_id" :value="userType.md_user_type_id">
                          {{ userType.user_type_name }}
                      </option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Modal footer -->
        <div class="modal-footer">
          <button class="btn btn-primary">Update User</button>
          <button type="button" class="btn btn-danger">Cancel</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal" id="myModalDel">
    <div class="modal-dialog">
      <div class="modal-content">
        
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">Delete User</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <!-- Modal body -->
        <div class="modal-body">
          Do you want to Delete....
        </div>
        
        <!-- Modal footer -->
        <div class="modal-footer">
          <button class="btn btn-success">Yes</button>
          <button type="button" class="btn btn-danger">No</button>
        </div>
      </div>
    </div>
  </div>

</template>

  <!-- <script setup>
    import { useForm } from '@inertiajs/vue3'
    import { onMounted } from 'vue'
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

    


  </script> -->

<script setup>
  import { ref, onMounted } from 'vue'
  import axios from 'axios'
  import { useForm } from '@inertiajs/vue3'

  import Header from '../Common/Header.vue'
  import Sidebar from '../Common/Sidebar.vue'
  import Footer from '../Common/Footer.vue'

  const form = useForm({
    first_name: '',
    last_name: '',
    designation: '',
    mobile: '',
    email: '',
    program_division: '',
    password: '',
    user_type: [],
  })

  const users = ref([]) // reactive users array
  const selectedUser = ref(null) // user selected for editing

  // Fetch users from API
  const getUsers = async () => {
    try {
      const response = await axios.get('/users') // Adjust your endpoint
      console.log('Fetched users:', response.data)
      users.value = response.data
    } catch (error) {
      console.error('Failed to fetch users:', error)
    }
  }
   // This will hold dropdown options
    const programDivisions = ref([])
    const userTypes = ref([])

    onMounted(() => {
      const fetchData = async () => {
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

        try {
          const responseUsers = await axios.get('/users')
          users.value = responseUsers.data
        } catch (error) {
          console.error('Failed to fetch users:', error)
        }
      }

      fetchData()
    })


  // onMounted(() => {
  //   getUsers()

  //    try {
  //       const response = await axios.get('/md-program-divisions')
  //       programDivisions.value = response.data
  //     } catch (error) {
  //       console.error('Failed to fetch program divisions', error)
  //     }

  //     try {
  //       const responseMUT = await axios.get('/md-user-types')
  //       userTypes.value = responseMUT.data
  //     } catch (error) {
  //       console.error('Failed to fetch user types', error)
  //     }
  // })

  // Modal open handler
  const openEditModal = (user) => {
    selectedUser.value = { ...user } // shallow copy to avoid live binding
    const modal = new bootstrap.Modal(document.getElementById('myModal'))
    modal.show()
  }

  const openDeleteModal = (user) => {
    selectedUser.value = user
    const modal = new bootstrap.Modal(document.getElementById('myModalDel'))
    modal.show()
  }
</script>
