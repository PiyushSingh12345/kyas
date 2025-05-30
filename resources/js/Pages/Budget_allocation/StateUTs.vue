<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
        <div class="container">
          <div class="page-inner allinsideform">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Master Data </h3>
              <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                  <a href="login.html">
                    <i class="icon-home"></i>
                  </a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">States/UTs and their tagging </a>
                </li>
              </ul>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">States/UTs and their Description</div>
                    <div v-if="successMessage" class="alert alert-success">
                      {{ successMessage }}
                    </div>
                  </div>

                   <form @submit.prevent="submit">
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label for="email2">States/UTs</label>
                            <input v-model="form.name" type="text" class="form-control" />
                            <div v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</div>

                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label for="email2"> Description</label>
                            <input v-model="form.description" type="text" class="form-control" />
                            <div v-if="form.errors.description" class="text-danger">{{ form.errors.description }}</div>
            
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="card-action">
                      <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                   </form>
                </div>
              </div>
            </div>

            
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">States/UTs and their tagging as NER and UTs with or without Legislature</div>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>S. No.</th>
                            <th>State/UT Name</th>
                            <th>Description</th>
                          </tr>
                        </thead>
                        <tbody>
                           <tr v-for="(state, index) in states" :key="state.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ state.name }}</td>
                            <td>{{ state.description }}</td>
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
</template>

<script setup>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

import { defineProps,computed } from 'vue'

import { useForm, usePage } from '@inertiajs/vue3'


const page = usePage()

const successMessage = computed(() => page.props.flash?.success || null)



const props = defineProps({
  states: Array
})

const form = useForm({
  name: '',
  description: ''
})

const submit = () => {
  form.post(route('states.store'), {
    preserveScroll: true,
    
    onSuccess: () => form.reset()
  })
}
</script>
