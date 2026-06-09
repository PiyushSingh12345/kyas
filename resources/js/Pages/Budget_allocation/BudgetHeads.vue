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
                            <option value="Gen">Gen</option>
                            <option value="SC">SC</option>
                            <option value="ST">ST</option>
                            <option value="Capital-Gen">Capital-Gen</option>
                            <option value="Capital-SC">Capital-SC</option>
                            <option value="Capital-ST">Capital-ST</option>
                            <option value="DAJUGA">DAJUGA</option>
                            <option value="Others">Others</option>
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
                  
                  <!-- File Upload Section starts here -->
                  <div class="card-body border-top">
                    <!-- File Upload 1st Section commented out for now -->
                    <!-- <div class="row"> -->
                      <!-- <div class="col-md-12"> -->
                        <!-- <div class="form-group"> -->
                          <!-- <label class="form-label fw-bold">Upload Budget Head Data (PDF)</label>
                          <div class="input-group">
                            <input 
                              type="file" 
                              class="form-control" 
                              @change="handleFileUpload"
                              accept=".pdf"
                              ref="fileInput"
                            />
                            <button 
                              type="button" 
                              class="btn btn-success" 
                              @click="uploadFile"
                              :disabled="!selectedFile || uploading"
                            >
                              <i class="fas fa-upload me-2"></i>
                              {{ uploading ? 'Uploading...' : 'Upload File' }}
                            </button>
                          </div>
                          <div v-if="selectedFile" class="mt-2">
                            <small class="text-muted">
                              Selected file: {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
                            </small>
                          </div> -->
                          <!-- <div v-if="uploadError" class="alert alert-danger mt-2">
                            {{ uploadError }}
                          </div>
                          <div v-if="uploadSuccess" class="alert alert-success mt-2">
                            {{ uploadSuccess }}
                          </div> -->
                          
                          <!-- Debug Info -->
                          <!-- <div v-if="extractedData" class="alert alert-info mt-2">
                            <strong>Debug:</strong> extractedData is set with {{ extractedData.total_lines }} lines
                          </div> -->
                          
                          <!-- Show Modal Button -->
                          <!-- <div v-if="extractedData" class="mt-3">
                            <button 
                              type="button" 
                              class="btn btn-primary" 
                              @click="showPreviewModal"
                            >
                              <i class="fas fa-eye me-2"></i>
                              Preview Extracted Data ({{ extractedData.total_lines }} lines)
                            </button>
                          </div> -->
                        <!-- </div> -->
                      <!-- </div> -->
                    <!-- </div> -->

                   <!-- File Upload 2nd Section starts here -->
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <div class="form-group">
                          <label class="form-label fw-bold">Upload Budget Head Table Data (PDF)</label>
                          <div class="input-group">
                            <input
                              type="file"
                              class="form-control"
                              @change="handleTableFileUpload"
                              accept=".pdf"
                              ref="tableFileInput"
                              :disabled="tableUploading || tableProcessing"
                            />
                            <button
                              type="button"
                              class="btn btn-success"
                              @click="uploadTableFile"
                              :disabled="!selectedTableFile || tableUploading || tableProcessing"
                            >
                              <span v-if="tableUploading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                              <i v-else class="fas fa-upload me-2"></i>
                              {{ tableUploading ? 'Processing...' : 'Upload File' }}
                            </button>
                          </div>
                          <small class="text-muted d-block mt-1">
                            Upload table-format PDF with "Head of account/ Scheme &amp; Programme" and "Krishonnati Yojna" section.
                            Scanned/image PDFs may take 2–4 minutes to process.
                          </small>
                          <div v-if="tableUploading" class="alert alert-warning mt-2 mb-0 d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-warning me-2" role="status">
                              <span class="visually-hidden">Loading...</span>
                            </div>
                            <span>Processing PDF… scanned files can take a few minutes. Please wait.</span>
                          </div>
                          <div v-if="selectedTableFile" class="mt-2">
                            <small class="text-muted">
                              Selected file: {{ selectedTableFile.name }} ({{ formatFileSize(selectedTableFile.size) }})
                            </small>
                          </div>
                          <div v-if="tableUploadError" class="alert alert-danger mt-2">
                            {{ tableUploadError }}
                          </div>
                          <div v-if="tableUploadSuccess" class="alert alert-success mt-2">
                            {{ tableUploadSuccess }}
                          </div>
                          <div v-if="tableExtractedData" class="mt-3">
                            <button
                              type="button"
                              class="btn btn-primary"
                              @click="showTablePreviewModal"
                            >
                              <i class="fas fa-eye me-2"></i>
                              Preview Extracted Data ({{ tableExtractedData.total_items }} items)
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
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
                    
                    <!-- Pagination Controls -->
                    <div v-if="pagination && pagination.total > 0" class="d-flex justify-content-between align-items-center mt-3">
                      <div class="d-flex align-items-center">
                        <label class="me-2">Show:</label>
                        <select v-model="itemsPerPage" class="form-select form-select-sm" style="width: 70px;" @change="changePerPage">
                          <option value="5">5</option>
                          <option value="10">10</option>
                          <option value="25">25</option>
                          <option value="50">50</option>
                        </select>
                        <span class="ms-2">entries</span>
                        <span class="ms-3">
                          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                        </span>
                      </div>
                      
                      <nav>
                        <ul class="pagination pagination-sm mb-0">
                          <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <button class="page-link" @click="goToFirstPage" :disabled="currentPage === 1">
                              <i class="fas fa-angle-double-left"></i>
                            </button>
                          </li>
                          <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <button class="page-link" @click="goToPreviousPage" :disabled="currentPage === 1">
                              <i class="fas fa-angle-left"></i>
                            </button>
                          </li>
                          
                          <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === currentPage, disabled: page === '...' }">
                            <button v-if="page !== '...'" class="page-link" @click="goToPage(page)">{{ page }}</button>
                            <span v-else class="page-link">{{ page }}</span>
                          </li>
                          
                          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <button class="page-link" @click="goToNextPage" :disabled="currentPage === totalPages">
                              <i class="fas fa-angle-right"></i>
                            </button>
                          </li>
                          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <button class="page-link" @click="goToLastPage" :disabled="currentPage === totalPages">
                              <i class="fas fa-angle-double-right"></i>
                            </button>
                          </li>
                        </ul>
                      </nav>
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
  
  <!-- Preview Modal -->
  <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="previewModalLabel">
            <i class="fas fa-file-pdf me-2"></i>
            Extracted Budget Data Preview
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>File:</strong> {{ selectedFile?.name || 'Unknown' }} | 
            <strong>Extracted:</strong> {{ extractedData?.total_items || 0 }} items from "Krishonnati Yojna" to "National Mission on Natural Farming"
            <br>
            <small class="text-muted">
              <i class="fas fa-database me-1"></i>
              Clicking "Proceed" will save budget heads to database and create corresponding budget phase records for BE 2025-26.
            </small>
          </div>
          
          <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-bordered table-striped">
              <thead class="table-dark sticky-top">
                <tr>
                  <th style="width: 25%;">Budget Head</th>
                  <th style="width: 40%;">Head Description</th>
                  <th style="width: 15%;">Category</th>
                  <!-- <th style="width: 20%;">BE 2024-25</th> -->
                  <th style="width: 20%;">Budget Amount in Lakhs (2025-2026)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in extractedData?.structured_data || []" :key="index">
                    <td style="font-family: monospace; font-size: 0.9em;">
                      <strong>{{ item.code }}</strong>
                    </td>
                    <td class="text-end fw-bold" style="font-family: monospace;">
                      {{ item.item }}
                    </td>
                    <td class="text-center">
                      <!-- <span :class="getCategoryClass(item.code)" class="badge"> -->
                        {{ calculateCategory(item.code) }}
                      <!-- </span> -->
                    </td>
                  <!-- <td class="text-end fw-bold" style="font-family: monospace;">
                    {{ formatAmount(item.be_2024_25) }}
                  </td> -->
                  <td class="text-end fw-bold" style="font-family: monospace;">
                    {{ formatAmount(item.be_2025_26) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <div class="d-flex justify-content-between w-100">
            <div>
              <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                Extracted on {{ new Date().toLocaleString() }}
              </small>
            </div>
            <div class="btn-group">
              <button 
                type="button" 
                class="btn btn-success" 
                @click="proceedWithImport"
                :disabled="processing"
              >
                <i class="fas fa-check me-2"></i>
                {{ processing ? 'Processing...' : 'Proceed' }}
              </button>
              <button 
                type="button" 
                class="btn btn-secondary" 
                @click="handleCancel"
                :disabled="processing"
              >
                <i class="fas fa-times me-2"></i>
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Format Preview Modal -->
  <div class="modal fade" id="tablePreviewModal" tabindex="-1" aria-labelledby="tablePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tablePreviewModalLabel">
            <i class="fas fa-file-pdf me-2"></i>
            Extracted Budget Table Data Preview
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>File:</strong> {{ selectedTableFile?.name || 'Unknown' }} |
            <strong>Extracted:</strong> {{ tableExtractedData?.total_items || 0 }} items from "Krishonnati Yojna" section
            <br>
            <strong>Financial Year(s):</strong>
            {{ tableFinancialYearsLabel }}
            <br>
            <small class="text-muted">
              <i class="fas fa-database me-1"></i>
              Clicking "Proceed" will save budget heads to database and create corresponding budget phase records.
            </small>
          </div>

          <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-bordered table-striped">
              <thead class="table-dark sticky-top">
                <tr>
                  <th style="width: 20%;">Budget Head</th>
                  <th style="width: 35%;">Head Description</th>
                  <th style="width: 12%;">Category</th>
                  <th style="width: 13%;">Financial Year</th>
                  <th style="width: 20%;">Budget Amount (Lakhs)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in tableExtractedData?.structured_data || []" :key="index">
                  <td style="font-family: monospace; font-size: 0.9em;">
                    <strong>{{ item.code }}</strong>
                  </td>
                  <td>{{ item.item }}</td>
                  <td class="text-center">{{ calculateCategory(item.code) }}</td>
                  <td class="text-center">{{ item.financial_year || 'N/A' }}</td>
                  <td class="text-end fw-bold" style="font-family: monospace;">
                    {{ formatTableAmount(item.budget_amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <div class="d-flex justify-content-between w-100">
            <div>
              <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                Extracted on {{ new Date().toLocaleString() }}
              </small>
            </div>
            <div class="btn-group">
              <button
                type="button"
                class="btn btn-success"
                @click="proceedWithTableImport"
                :disabled="tableProcessing"
              >
                <span v-if="tableProcessing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="fas fa-check me-2"></i>
                {{ tableProcessing ? 'Processing...' : 'Proceed' }}
              </button>
              <button
                type="button"
                class="btn btn-secondary"
                @click="handleTableCancel"
                :disabled="tableProcessing"
              >
                <i class="fas fa-times me-2"></i>
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import { computed, ref, defineProps, watch } from 'vue'

const parseJsonSafely = async (response) => {
  const responseText = await response.text()
  if (!responseText) return {}
  try {
    return JSON.parse(responseText)
  } catch {
    const error = new Error('Invalid JSON response from server.')
    error.responseText = responseText
    throw error
  }
}

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const csrfFetch = async (url, options = {}) => {
  const token = getCsrfToken()
  if (!token) {
    throw new Error('CSRF token not found. Please refresh the page and try again.')
  }

  let body = options.body
  if (body instanceof FormData && !body.has('_token')) {
    body.append('_token', token)
  }

  const response = await fetch(url, {
    ...options,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': token,
      ...options.headers,
    },
    body,
  })

  const data = await parseJsonSafely(response)

  if (response.status === 419) {
    throw new Error(data.message || 'CSRF token mismatch. Please refresh the page and try again.')
  }

  if (!response.ok) {
    throw new Error(data.message || 'Request failed. Please try again.')
  }

  return data
}

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
  BudgetHeads: Array,
  pagination: Object
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success || null)
const editingId = ref(null)

// Pagination variables
const currentPage = ref(props.pagination?.current_page || 1)
const itemsPerPage = ref(props.pagination?.per_page || 10)

// Watch for pagination changes from props
watch(() => props.pagination, (newPagination) => {
  if (newPagination) {
    currentPage.value = newPagination.current_page
    itemsPerPage.value = newPagination.per_page
  }
}, { immediate: true })

// Pagination computed properties
const totalPages = computed(() => {
  return props.pagination?.last_page || 1
})

const startIndex = computed(() => {
  return props.pagination?.from || 0
})

const endIndex = computed(() => {
  return props.pagination?.to || 0
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  // Always show first page
  pages.push(1)
  
  // Show pages around current page
  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)
  
  if (start > 2) {
    pages.push('...')
  }
  
  for (let i = start; i <= end; i++) {
    if (i > 1 && i < total) {
      pages.push(i)
    }
  }
  
  if (end < total - 1) {
    pages.push('...')
  }
  
  // Always show last page if there are more than 1 page
  if (total > 1) {
    pages.push(total)
  }
  
  return pages
})

// File upload related reactive variables
const selectedFile = ref(null)
const uploading = ref(false)
const processing = ref(false)
const uploadError = ref('')
const uploadSuccess = ref('')
const extractedData = ref(null)
const fileInput = ref(null)

const selectedTableFile = ref(null)
const tableUploading = ref(false)
const tableProcessing = ref(false)
const tableUploadError = ref('')
const tableUploadSuccess = ref('')
const tableExtractedData = ref(null)
const tableFileInput = ref(null)

const tableFinancialYearsLabel = computed(() => {
  const years = tableExtractedData.value?.financial_years || []
  if (years.length === 0) {
    return 'Not detected'
  }
  return years.map((year) => `BE ${year}`).join(', ')
})

const form = useForm({
  budget: '',
  description: '',
  category: 'Gen',
  status: '1'
})

// Submit (Create or Update)
const submit = () => {
  // Show confirmation dialog
  if (!confirm('Are you sure to freeze?')) {
    return // Cancel the operation if user clicks No
  }

  if (editingId.value) {
    form.put(route('BudgetHead.update', editingId.value), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
        form.category = 'Gen' // Reset to default category
        editingId.value = null
      }
    })
  } else {
    form.post(route('BudgetHead.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
        form.category = 'Gen' // Reset to default category
      }
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
  form.category = 'Gen' // Reset to default category
}

// File upload methods
const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file type
    const allowedTypes = ['application/pdf']
    if (!allowedTypes.includes(file.type)) {
      uploadError.value = 'Please select a valid PDF file.'
      selectedFile.value = null
      return
    }
    
    // Validate file size (max 10MB)
    const maxSize = 10 * 1024 * 1024 // 10MB
    if (file.size > maxSize) {
      uploadError.value = 'File size must be less than 10MB.'
      selectedFile.value = null
      return
    }
    
    selectedFile.value = file
    uploadError.value = ''
    uploadSuccess.value = ''
    extractedData.value = null
  }
}

const uploadFile = () => {
  if (!selectedFile.value) {
    uploadError.value = 'Please select a file to upload.'
    return
  }
  
  uploading.value = true
  uploadError.value = ''
  uploadSuccess.value = ''
  
  const formData = new FormData()
  formData.append('file', selectedFile.value)

  csrfFetch('/budget-heads/upload', {
    method: 'POST',
    body: formData,
  })
  .then(data => {
    console.log('Upload success response:', data)
    if (data.success) {
      uploadSuccess.value = 'File processed successfully! Preview the extracted data below.'
      extractedData.value = data.data
      console.log('Extracted data set:', extractedData.value)
    } else {
      uploadError.value = data.message || 'Upload failed. Please try again.'
    }
  })
  .catch(error => {
    console.log('Upload error:', error)
    uploadError.value = error?.message || 'Upload failed. Please try again.'
  })
  .finally(() => {
    uploading.value = false
  })
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Accept extracted data and import to database
const acceptExtractedData = () => {
  if (!extractedData.value) return
  
  processing.value = true
  
  const token = getCsrfToken()
  if (!token) {
    uploadError.value = 'CSRF token not found. Please refresh the page and try again.'
    processing.value = false
    return
  }

  csrfFetch('/budget-heads/import', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      _token: token,
      structured_data: extractedData.value.structured_data,
      file_name: selectedFile.value?.name || 'Unknown',
    }),
  })
  .then(data => {
    if (data.success) {
      uploadSuccess.value = data.message || 'Data imported successfully!'
      // Close the modal after successful import
      closePreviewModal()
      // Refresh the budget heads list
      router.reload({ only: ['BudgetHeads'] })
    } else {
      uploadError.value = data.message || 'Import failed. Please try again.'
    }
  })
  .catch(error => {
    console.log('Import error:', error)
    uploadError.value = error?.message || 'Import failed. Please try again.'
  })
  .finally(() => {
    processing.value = false
  })
}

// Cancel extracted data preview
const cancelExtractedData = () => {
  extractedData.value = null
  selectedFile.value = null
  uploadSuccess.value = ''
  uploadError.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

// Modal functions
const showPreviewModal = () => {
  // Use Bootstrap modal
  try {
    const modalElement = document.getElementById('previewModal')
    if (modalElement) {
      const modal = new bootstrap.Modal(modalElement)
      modal.show()
    }
  } catch (error) {
    console.error('Error showing modal:', error)
    // Fallback: show alert with data
    alert('Modal error. Please check console for details.')
  }
}

const closePreviewModal = () => {
  try {
    const modalElement = document.getElementById('previewModal')
    if (modalElement) {
      const modal = bootstrap.Modal.getInstance(modalElement)
      if (modal) {
        modal.hide()
      }
    }
  } catch (error) {
    console.error('Error closing modal:', error)
  }
  
  // Reset file upload data
  extractedData.value = null
  selectedFile.value = null
  uploadSuccess.value = ''
  uploadError.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const proceedWithImport = () => {
  // Show confirmation dialog
  if (!confirm('Are you sure to freeze?')) {
    return // Cancel the operation if user clicks No
  }
  
  acceptExtractedData()
}

// Pagination functions
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    router.get(route('budget-heads'), { page: page }, { 
      preserveState: true,
      preserveScroll: true 
    })
  }
}

const goToFirstPage = () => {
  goToPage(1)
}

const goToLastPage = () => {
  goToPage(totalPages.value)
}

const goToPreviousPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const goToNextPage = () => {
  if (currentPage.value < totalPages.value) {
    goToPage(currentPage.value + 1)
  }
}

const changePerPage = () => {
  currentPage.value = 1 // Reset to first page when changing per page
  router.get(route('budget-heads'), { 
    per_page: itemsPerPage.value,
    page: 1 
  }, { 
    preserveState: true,
    preserveScroll: true 
  })
}

const handleCancel = () => {
  closePreviewModal()
}

const handleTableFileUpload = (event) => {
  const file = event.target.files[0]
  if (!file) {
    return
  }

  if (file.type !== 'application/pdf') {
    tableUploadError.value = 'Please select a valid PDF file.'
    selectedTableFile.value = null
    return
  }

  const maxSize = 10 * 1024 * 1024
  if (file.size > maxSize) {
    tableUploadError.value = 'File size must be less than 10MB.'
    selectedTableFile.value = null
    return
  }

  selectedTableFile.value = file
  tableUploadError.value = ''
  tableUploadSuccess.value = ''
  tableExtractedData.value = null
}

const uploadTableFile = () => {
  if (!selectedTableFile.value) {
    tableUploadError.value = 'Please select a file to upload.'
    return
  }

  tableUploading.value = true
  tableUploadError.value = ''
  tableUploadSuccess.value = ''

  const formData = new FormData()
  formData.append('file', selectedTableFile.value)

  csrfFetch('/budget-heads/upload-table', {
    method: 'POST',
    body: formData,
  })
    .then((data) => {
      if (data.success) {
        tableUploadSuccess.value = 'File processed successfully! Preview the extracted data below.'
        tableExtractedData.value = data.data
      } else {
        tableUploadError.value = data.message || 'Upload failed. Please try again.'
      }
    })
    .catch((error) => {
      tableUploadError.value = error?.message || 'Upload failed. Please try again.'
    })
    .finally(() => {
      tableUploading.value = false
    })
}

const acceptTableExtractedData = () => {
  if (!tableExtractedData.value) {
    return
  }

  tableProcessing.value = true

  const token = getCsrfToken()
  if (!token) {
    tableUploadError.value = 'CSRF token not found. Please refresh the page and try again.'
    tableProcessing.value = false
    return
  }

  csrfFetch('/budget-heads/import-table', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      _token: token,
      structured_data: tableExtractedData.value.structured_data,
      financial_years: tableExtractedData.value.financial_years || [],
      file_name: selectedTableFile.value?.name || 'Unknown',
    }),
  })
    .then((data) => {
      if (data.success) {
        tableUploadSuccess.value = data.message || 'Data imported successfully!'
        closeTablePreviewModal()
        router.reload({ only: ['BudgetHeads'] })
      } else {
        tableUploadError.value = data.message || 'Import failed. Please try again.'
      }
    })
    .catch((error) => {
      tableUploadError.value = error?.message || 'Import failed. Please try again.'
    })
    .finally(() => {
      tableProcessing.value = false
    })
}

const showTablePreviewModal = () => {
  try {
    const modalElement = document.getElementById('tablePreviewModal')
    if (modalElement) {
      const modal = new bootstrap.Modal(modalElement)
      modal.show()
    }
  } catch (error) {
    console.error('Error showing table preview modal:', error)
  }
}

const closeTablePreviewModal = () => {
  try {
    const modalElement = document.getElementById('tablePreviewModal')
    if (modalElement) {
      const modal = bootstrap.Modal.getInstance(modalElement)
      if (modal) {
        modal.hide()
      }
    }
  } catch (error) {
    console.error('Error closing table preview modal:', error)
  }

  tableExtractedData.value = null
  selectedTableFile.value = null
  tableUploadSuccess.value = ''
  tableUploadError.value = ''
  if (tableFileInput.value) {
    tableFileInput.value.value = ''
  }
}

const proceedWithTableImport = () => {
  if (!confirm('Are you sure to freeze?')) {
    return
  }

  acceptTableExtractedData()
}

const handleTableCancel = () => {
  closeTablePreviewModal()
}

// Helper function to format amount
const formatAmount = (amount) => {
  if (!amount || amount === 'null' || amount === '') {
    return 'N/A'
  }
  
  // Remove any non-numeric characters except dots and commas
  const cleanAmount = amount.toString().replace(/[^\d.,]/g, '')
  
  if (cleanAmount === '') {
    return 'N/A'
  }
  
  return cleanAmount
}

const formatTableAmount = (amount) => {
  if (!amount || amount === 'null' || amount === '') {
    return '0'
  }

  const cleanAmount = amount.toString().replace(/[^\d.,]/g, '')

  if (cleanAmount === '') {
    return '0'
  }

  return cleanAmount
}

// Helper function to calculate category based on budget head code
const calculateCategory = (code) => {
  // Remove any non-digit characters and get the numeric part
  const numericCode = code.replace(/[^0-9]/g, '')
  
  // If code is not long enough, return 'Others'
  if (numericCode.length < 9) {
    return 'Others'
  }
  
  // Get last 2 digits
  const lastTwoDigits = numericCode.slice(-2)
  
  // Get middle 3 digits (positions 7-9)
  const middleThreeDigits = numericCode.slice(6, 9)

  // Get Second Last 2 digits (positions 12-13)  12341212312.12.12
  const secondLastTwoDigits = numericCode.slice(11, 13)
  // console.log('Second Last 2 Digits:', secondLastTwoDigits)
  // return false;
  
  // If last 2 digits are not "31" or "35", return "Others"
  if (lastTwoDigits !== '31' && lastTwoDigits !== '35') {
    return 'Others'
  }

  // if last two digits is '31' and second last two digits is '01' then it will be category of DAJUGA
  if (lastTwoDigits === '31' && secondLastTwoDigits == '01') {
    return 'DAJUGA'
  }
  
  // Check middle 3 digits for different categories
  if (middleThreeDigits === '101' || middleThreeDigits === '342' || middleThreeDigits === '103') {
    // If last 2 digits is "35", return "Capital-Gen", else return "Gen"
    return lastTwoDigits === '35' ? 'Capital-Gen' : 'Gen'
  } else if (middleThreeDigits === '789') {
    // If last 2 digits is "35", return "Capital-SC", else return "SC"
    return lastTwoDigits === '35' ? 'Capital-SC' : 'SC'
  } else if (middleThreeDigits === '796') {
    // If last 2 digits is "35", return "Capital-ST", else return "ST"
    return lastTwoDigits === '35' ? 'Capital-ST' : 'ST'
  }
  
  // Default case
  return 'Others'
}

// Helper function to get category class for styling
const getCategoryClass = (code) => {
  const category = calculateCategory(code)
  
  switch (category) {
    case 'Capital-Gen':
    case 'Gen':
      return 'badge-success'
    case 'Capital-SC':
    case 'SC':
      return 'badge-primary'
    case 'Capital-ST':
    case 'ST':
      return 'badge-danger'
    case 'DAJUGA':
      return 'badge-warning'
    case 'Others':
    default:
      return 'badge-secondary'
  }
}
</script>
