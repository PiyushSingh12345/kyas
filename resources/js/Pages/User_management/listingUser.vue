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
              <li class="nav-item"><a href="#">Edit User</a></li>
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
														<th>Name</th>
														<th>Mobile No.</th>
                            	<th>Email</th>
														<th>Designation</th>
														
														
														<th>Program Division</th>
												
														<th>User Type</th>
														<th>Action</th>
													</tr>
												</thead>
												<tbody>
                          <tr v-if="users.length === 0">
                            <td colspan="10" class="text-center">No users found</td>
                          </tr>
													<tr v-else v-for="(user,index) in users" :key="user.id">
														<th scope="row">{{ index + 1 }}</th>
														<td>{{ user.name }}</td>
														<td>{{ user.mobile_number }}</td>
															<td>{{ user.email }}</td>
														<td>{{ user.designation_id }}</td>
													
													
														<!-- <td>{{ user.program_division_id }}</td> -->
														<td>{{ user.program_division }}</td>
														
														<!-- <td>{{ user.user_type_id }}</td> -->
														<td>{{ user.user_type }}</td>
														<td>
															<a href="#" @click="openEditModal(user)" class="me-2" data-bs-toggle="modal" data-bs-target="#myModal">
																<i class="fas fa-edit"></i>
															</a>
															<a href="#" @click="openDeleteModal(user)" class="me-2" data-bs-toggle="modal" data-bs-target="#myModalDel">
																<i class="fas fa-trash"></i>
															</a>
														</td>
													</tr>
													<!-- <tr>
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
													</tr> -->
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
                    <label for="first_name">First Name</label>
                    <input type="text" v-model="form.first_name" class="form-control" name="first_name" id="first_name"
                      placeholder="First Name">
                  </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="last_name">Last Name</label>
                  <input type="text" v-model="form.last_name" class="form-control" name="last_name" id="last_name"
                    placeholder="Last Name">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="designation">Designation</label>
                  <input type="text" v-model="form.designation" class="form-control" name="designation" id="designation"
                    placeholder="Enter Designation">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="mobile">Mobile No.</label>
                  <input type="number" v-model="form.mobile_number" class="form-control" name="mobile" id="mobile"
                    placeholder="Enter Mobile">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="email">Email id.</label>
                  <input type="email" v-model="form.email" class="form-control" name="email" id="email"
                    placeholder="Enter Email Id">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="program_division">Program Division</label>
                  <select name="program_division" v-model="form.program_division_id" id="program_division"  class="form-select">
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
                  <input type="password" v-model="form.password" class="form-control" placeholder="Enter Password" name="password" id="password">
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label for="user_type">User Type</label>

                  <select name="user_type" v-model="form.user_type_id" id="user_type" class="form-select" multiple>
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
          <button @click="submitEditForm" class="btn btn-primary">Update User</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
          Are you sure you want to delete {{ selectedUser?.name }}?
        </div>
        
        <!-- Modal footer -->
        <div class="modal-footer">
          <button class="btn btn-success" @click="confirmDelete">Yes</button>
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No</button>
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import axios from 'axios'
  import { useForm } from '@inertiajs/vue3'

  import Header from '../Common/Header.vue'
  import Sidebar from '../Common/Sidebar.vue'
  import Footer from '../Common/Footer.vue'

  const users = ref([]) // reactive users array
  const selectedUser = ref(null) // user selected for editing

  const form = useForm({
    id: null,
    name: '',
    first_name: '',
    last_name: '',
    designation: '',
    mobile_number: '',
    email: '',
    program_division: '',
    program_division_id: '',
    password: '',
    user_type: [],
    user_type_id: [],
  })

  

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
      fetchUsers();
      fetchProgramDivisions();
      fetchUserTypes();
    });

    const fetchUsers = async () => {
      try {
        const response = await axios.get('/users');
        users.value = response.data;
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    };

    const fetchProgramDivisions = async () => {
      // Fetch program divisions logic
      try {
        const response = await axios.get('/md-program-divisions')
        programDivisions.value = response.data
      } catch (error) {
        console.error('Failed to fetch program divisions', error)
      }
    };

    const fetchUserTypes = async () => {
      // Fetch user types logic
      try {
        const response = await axios.get('/md-user-types')
        userTypes.value = response.data
      } catch (error) {
        console.error('Failed to fetch user types', error)
      }
    };


  
    const openEditModal = (user) => {
      selectedUser.value = user;
      form.id = user.id;
      form.name = (user.name)? user.name : `${user.first_name} ${user.last_name}`;

      const nameParts = form.name.split(' ');
      const first_name = (nameParts[0]) ? nameParts[0] : '';
      const last_name = (nameParts[1]) ? nameParts[1] : '';

      form.first_name = (user.first_name)? user.first_name : first_name;
      form.last_name = (user.last_name)? user.last_name : last_name;
      form.email = user.email;
      form.mobile_number = user.mobile_number;
      form.designation = user.designation_id;
      form.program_division_id = user.program_division_id;  
      form.program_division = user.program_division;
      // form.user_type = user.user_type_ids; // Adjust based on your data structure
      form.user_type = user.user_type; // Adjust based on your data structure
      // form.user_type_id = user.user_type_id; // Adjust based on your data structure
      // Convert comma-separated string to array of numbers
      form.user_type_id = user.user_type_id
    ? user.user_type_id.split(',').map(id => parseInt(id.trim()))
    : [];
      form.password = user.password;
      const modal = new bootstrap.Modal(document.getElementById('myModal'));
      modal.show();
    };

    const submitEditForm = () => {
      form.put(`/users/${form.id}`, {
        onSuccess: () => {
          const modal = bootstrap.Modal.getInstance(document.getElementById('myModal'));
          modal.hide();
          this.$router.go();
          fetchUsers();
        },
        onError: (errors) => {
          console.error('Error updating user:', errors);
        },
      });
    };

    


  const openDeleteModal = (user) => {
    selectedUser.value = user;
    const modal = new bootstrap.Modal(document.getElementById('myModalDel'));
    modal.show();
  };

  const confirmDelete = () => {
    axios
      .delete(`/users/${selectedUser.value.id}`)
      .then(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('myModalDel'));
        modal.hide();
        fetchUsers();
      })
      .catch((error) => {
        console.error('Error deleting user:', error);
      });
  };
</script>
