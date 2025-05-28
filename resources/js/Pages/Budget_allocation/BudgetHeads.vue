<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <!-- Header -->
          <div class="page-header">
            <h3 class="fw-bold mb-3">Master Data</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Budget Heads and their Description</a>
              </li>
            </ul>
          </div>

          <!-- Flash Message -->
          <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

          <!-- Budget Head Form -->
          <div class="row mt-3">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Budget Heads and their Description</div>
                </div>
                <form @submit.prevent="submit">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label>Budget Head</label>
                          <input v-model="form.budget" type="text" class="form-control" />
                          <div v-if="form.errors.budget" class="text-danger">{{ form.errors.budget }}</div>
                        </div>
                      </div>
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label>Head Description</label>
                          <input v-model="form.description" type="text" class="form-control" />
                          <div v-if="form.errors.description" class="text-danger">{{ form.errors.description }}</div>
                        </div>
                      </div>
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label>Category</label>
                          <select v-model="form.category" class="form-select">
                            <option value="">--- Select ---</option>
                            <option value="SC">SC</option>
                            <option value="ST">ST</option>
                            <option value="Gen">Gen</option>
                          </select>
                          <div v-if="form.errors.category" class="text-danger">{{ form.errors.category }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card-action">
                    <button type="submit" class="btn btn-primary me-2">
                      {{ editingId ? 'Update' : 'Submit' }}
                    </button>
                    <button v-if="editingId" type="button" class="btn btn-secondary" @click="cancelEdit">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Budget Head Listing -->
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">List of Budget Heads for Krishionnati Yojana</div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>S. No.</th>
                          <th>Budget Head</th>
                          <th>Head Description</th>
                          <th>Category</th>
                          <th>Active Head </th>
                          
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in BudgetHeads || []" :key="item.id">

                          <td>{{ index + 1 }}</td>
                          <td>{{ item.budget }}</td>
                          <td>{{ item.description }}</td>
                          <td>{{ item.category }}</td>
                         <td>
                            <button
                              :class="['btn btn-sm', item.status === 1 ? 'btn-success' : 'btn-danger']"
                              @click="toggleStatus(item)"
                            >
                              {{ item.status === 1 ? 'Active' : 'Inactive' }}
                            </button>
                          </td>

                         
                          <td>
                            <a @click="editBudgetHead(item)" href="#" class="me-2" data-bs-toggle="modal" data-bs-target="#myModal">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a @click="deleteBudgetHead(item.id)" class="me-2" data-bs-toggle="modal" data-bs-target="#myModalDel">
                              <i class="fas fa-trash"></i>
                            </a>
                          </td>
                        </tr>
                        <tr v-if="BudgetHeads.length === 0">
                          <td colspan="6" class="text-center">No records found.</td>
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
import { useForm, usePage, router } from '@inertiajs/vue3'
import { computed, ref, defineProps } from 'vue'

const deleteBudgetHead = (id) => {
  if (confirm('Are you sure you want to deactivate this Budget Head?')) {
    router.delete(route('BudgetHead.destroy', id), {
      preserveScroll: true,
    })
  }
}

const toggleStatus = (item) => {
  const newStatus = item.status === 1 ? 0 : 1

  router.put(route('BudgetHead.toggleStatus', item.id), {
    status: newStatus
  }, {
    preserveScroll: true,
  })
}

const props = defineProps({
  BudgetHeads: Array
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success || null)
const editingId = ref(null)

const form = useForm({
  budget: '',
  description: '',
  category: '',
  status: '1'
})

// Submit (Create or Update)
const submit = () => {
  if (editingId.value) {
    form.put(route('BudgetHead.update', editingId.value), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
        editingId.value = null
      }
    })
  } else {
    form.post(route('BudgetHead.store'), {
      preserveScroll: true,
      onSuccess: () => form.reset()
    })
  }
}
// Start editing
const editBudgetHead = (item) => {
  editingId.value = item.id
  form.budget = item.budget
  form.description = item.description
  form.category = item.category
}

// Cancel edit
const cancelEdit = () => {
  editingId.value = null
  form.reset()
}
</script>
