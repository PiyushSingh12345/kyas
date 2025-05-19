<template>
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
                    <div
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
                    </div>

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
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

// Correct relative paths (from createUser.vue to Common/)
import Header from './Common/Header.vue'
import Footer from './Common/Footer.vue'
import Sidebar from './Common/Sidebar.vue'

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
</script>
