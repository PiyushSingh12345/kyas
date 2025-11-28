<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Agency Release</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">TSA</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">TSA</div>
                </div>

                <div class="card-body">
                  <!-- Flash Message -->
                  <div v-if="flashMessage.show" :class="`alert alert-${flashMessage.type} alert-dismissible fade show`" role="alert">
                    <i :class="flashMessage.icon"></i>
                    {{ flashMessage.message }}
                    <button type="button" class="btn-close" @click="hideFlashMessage" aria-label="Close"></button>
                  </div>

                  <form @submit.prevent="submitForm">
                    <div class="row">
                      <!-- Sanction Number -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="sanctionNumber">Sanction Number <span class="text-danger">*</span></label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="sanctionNumber" 
                            v-model="formData.sanctionNumber"
                            pattern="[A-Za-z0-9]+"
                            placeholder="Enter Sanction Number"
                            required
                          >
                        </div>
                      </div>

                      <!-- Date -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="date">Date <span class="text-danger">*</span></label>
                          <input 
                            type="date" 
                            class="form-control" 
                            id="date" 
                            v-model="formData.date"
                            required
                          >
                        </div>
                      </div>

                      <!-- Budget Head -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="budgetHead">Budget Head <span class="text-danger">*</span></label>
                          <select 
                            class="form-select" 
                            id="budgetHead" 
                            v-model="formData.budgetHead"
                            required
                          >
                            <option value="">--- Select Budget Head ---</option>
                            <option v-for="head in budgetHeads" :key="head.id" :value="head.budget">
                              {{ head.budget }} - {{ head.description }}
                            </option>
                          </select>
                        </div>
                      </div>

                      <!-- Purpose Of Grant -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="purposeOfGrant">Purpose Of Grant <span class="text-danger">*</span></label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="purposeOfGrant" 
                            v-model="formData.purposeOfGrant"
                            pattern="[A-Za-z\s]+"
                            placeholder="Enter Purpose Of Grant"
                            required
                          >
                        </div>
                      </div>

                      <!-- Program Division -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="programDivision">Program Division <span class="text-danger">*</span></label>
                          <select 
                            class="form-select" 
                            id="programDivision" 
                            v-model="formData.programDivision"
                            required
                          >
                            <option value="">--- Select Program Division ---</option>
                            <option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">
                              {{ division.division_name }}
                            </option>
                          </select>
                        </div>
                      </div>

                      <!-- Amount -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="amount">Amount <span class="text-danger">*</span></label>
                          <input 
                            type="number" 
                            class="form-control" 
                            id="amount" 
                            v-model="formData.amount"
                            step="0.01"
                            min="0"
                            placeholder="Enter Amount"
                            required
                          >
                        </div>
                      </div>

                      <!-- Central Implementing Agency -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="centralImplementingAgency">Central Implementing Agency <span class="text-danger">*</span></label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="centralImplementingAgency" 
                            v-model="formData.centralImplementingAgency"
                            pattern="[A-Za-z0-9\s]+"
                            placeholder="Enter Central Implementing Agency"
                            required
                          >
                        </div>
                      </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="card-footer">
                      <div class="form">
                        <div class="col-12 d-flex justify-content-center">
                          <button type="submit" class="btn btn-success me-1">Submit</button>
                          <button type="button" class="btn btn-danger me-1" @click="resetForm">Reset</button>
                        </div>
                      </div>
                    </div>
                  </form>
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
import { ref, onMounted, nextTick } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const formData = ref({
  sanctionNumber: '',
  date: '',
  budgetHead: '',
  purposeOfGrant: '',
  programDivision: '',
  amount: '',
  centralImplementingAgency: ''
})

const budgetHeads = ref([])
const programDivisions = ref([])

// Flash message state
const flashMessage = ref({
  show: false,
  type: 'success',
  message: '',
  icon: ''
})

const showFlashMessage = (type, message, icon) => {
  flashMessage.value = {
    show: true,
    type,
    message,
    icon
  }
  
  setTimeout(() => {
    hideFlashMessage()
  }, 5000)
}

const hideFlashMessage = () => {
  flashMessage.value.show = false
}

const fetchBudgetHeads = async () => {
  try {
    const response = await fetch('/api/budget-heads-by-major-head?major_head=2435')
    if (response.ok) {
      const data = await response.json()
      budgetHeads.value = data
      
      // Initialize select2 after data is loaded
      await nextTick()
      if (window.$ && window.$.fn && window.$.fn.select2) {
        window.$('#budgetHead').select2({
          theme: 'bootstrap',
          placeholder: '--- Select Budget Head ---',
          allowClear: true
        }).on('change', function() {
          formData.value.budgetHead = window.$(this).val()
        })
      }
    } else {
      console.error('Failed to fetch budget heads')
      showFlashMessage('danger', 'Failed to load budget heads', 'fas fa-exclamation-triangle')
    }
  } catch (error) {
    console.error('Error fetching budget heads:', error)
    showFlashMessage('danger', 'Error loading budget heads', 'fas fa-exclamation-triangle')
  }
}

const fetchProgramDivisions = async () => {
  try {
    const response = await fetch('/api/aap-program-divisions')
    if (response.ok) {
      programDivisions.value = await response.json()
    } else {
      console.error('Failed to fetch program divisions')
    }
  } catch (error) {
    console.error('Error fetching program divisions:', error)
  }
}

const resetForm = () => {
  formData.value = {
    sanctionNumber: '',
    date: '',
    budgetHead: '',
    purposeOfGrant: '',
    programDivision: '',
    amount: '',
    centralImplementingAgency: ''
  }
  
  // Reset select2
  if (window.$ && window.$('#budgetHead').data('select2')) {
    window.$('#budgetHead').val(null).trigger('change')
  }
  
  hideFlashMessage()
}

const submitForm = async () => {
  // Validation
  if (!formData.value.sanctionNumber || !formData.value.date || !formData.value.budgetHead || 
      !formData.value.purposeOfGrant || !formData.value.programDivision || 
      !formData.value.amount || !formData.value.centralImplementingAgency) {
    showFlashMessage('danger', 'Please fill in all required fields', 'fas fa-exclamation-triangle')
    return
  }

  try {
    // Prepare data with proper types
    const submitData = {
      sanctionNumber: formData.value.sanctionNumber,
      date: formData.value.date,
      budgetHead: formData.value.budgetHead,
      purposeOfGrant: formData.value.purposeOfGrant,
      programDivision: parseInt(formData.value.programDivision),
      amount: parseFloat(formData.value.amount),
      centralImplementingAgency: formData.value.centralImplementingAgency
    }

    const response = await fetch('/api/agency-release-tsa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(submitData)
    })

    if (response.ok) {
      const result = await response.json()
      showFlashMessage('success', 'TSA data saved successfully!', 'fas fa-check-circle')
      resetForm()
    } else {
      const errorData = await response.json().catch(() => ({}))
      const errorMessage = errorData.message || 'Failed to save data. Please try again.'
      showFlashMessage('danger', errorMessage, 'fas fa-exclamation-triangle')
    }
  } catch (error) {
    console.error('Error submitting form:', error)
    showFlashMessage('danger', 'An error occurred while submitting the form. Please try again.', 'fas fa-exclamation-triangle')
  }
}

onMounted(async () => {
  await fetchBudgetHeads()
  await fetchProgramDivisions()
})
</script>

<style scoped>
.alert {
  border-radius: 8px;
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-success {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color: #155724;
  border-left: 4px solid #28a745;
}

.alert-danger {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color: #721c24;
  border-left: 4px solid #dc3545;
}

.alert i {
  margin-right: 8px;
  font-size: 1.1em;
}

.btn-close {
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-close:hover {
  opacity: 1;
}

.text-danger {
  color: #dc3545;
}
</style>

