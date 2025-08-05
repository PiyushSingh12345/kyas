<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Master Data</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="login.html"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">States/UTs - PD/Component - SLS IDs Mapping</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <!-- Accordion Container -->
              <div class="accordion" id="stateUTsAccordion">
                
                <!-- Section 1: State-wise list of PD/Component and SLS -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section1 }" type="button" @click="toggleAccordion('section1')" aria-expanded="true" aria-controls="collapseOne">
                      <i class="fas fa-map-marker-alt me-2"></i>
                      State-wise list of PD/Component and SLS
                    </button>
                  </h2>
                  <div id="collapseOne" class="accordion-collapse" :class="{ 'show': accordionStates.section1 }" aria-labelledby="headingOne" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section1">
              <div class="card">
                <div class="card-body">
                  <!-- First Table: Static with Component Dropdown -->
                  <div class="table-responsive mt-1" style="width: 50%;">
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>Component</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>
                            <select v-model="selectedComponent" class="form-select">
                              <option value="">--- Select ---</option>
                              <option value="PD">PD Component</option>
                              <option value="SL">SLS ID</option>
                            </select>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Second Table: Conditionally rendered -->
                  <div class="table-responsive mt-1" v-if="selectedComponent">
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th v-if="selectedComponent === 'SL'">PD</th>
                          <th v-if="selectedComponent === 'SL'">State/UT</th>
                          <th v-if="selectedComponent === 'PD'">PD/Component</th>
                          <th v-if="selectedComponent === 'SL'">SLS ID</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, index) in formRows" :key="index">
                          <td v-if="selectedComponent === 'SL'">
                           <select v-model="row.slsPD" class="form-select">
                              <option value="">--- Select PD ---</option>
                              <option
                                v-for="(item, index) in savedData.filter(i => i.component === 'PD')"
                                :key="item.id"
                                :value="item.name"
                              >
                                {{ item.name }}
                              </option>

                            </select>

                          </td>

                          <td v-if="selectedComponent === 'SL'">
                           <select v-model="row.state" class="form-select">

                              <option value="">--- Select State ---</option>
                              <option v-for="state in states" :key="state.id" :value="state.id">
                                {{ state.name }}
                              </option>
                            </select>
                          </td>

                          <td v-if="selectedComponent === 'PD'">
                            <input v-model="row.pdComponent" type="text" class="form-control" placeholder="Enter PD/Component" />
                          </td>

                          <td v-if="selectedComponent === 'SL'">
                            <input v-model="row.slsId" type="text" class="form-control" placeholder="Enter SLS ID" />
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <!-- Add Row -->
                    <div class="col-md-12 col-lg-12">
                      <button class="btn btn-primary me-1 mb-4" @click="addRow">+ Add New</button>
                    </div>
                  </div>

                  <!-- Buttons -->
                  <div class="col-12 d-flex justify-content-center">
                    <button class="btn btn-success me-1" @click="submit">Submit</button>
                    <button class="btn btn-danger me-1">Reset</button>
                  </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 2: PD Component List -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section2 }" type="button" @click="toggleAccordion('section2')" aria-expanded="false" aria-controls="collapseTwo">
                      <i class="fas fa-list-alt me-2"></i>
                      PD Component List
                    </button>
                  </h2>
                  <div id="collapseTwo" class="accordion-collapse" :class="{ 'show': accordionStates.section2 }" aria-labelledby="headingTwo" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section2">
                      <div class="card">
                        <div class="card-body">
                          <div class="bg-primary text-white px-3 py-2 rounded mb-3">
                            <h5 class="mb-0">PD Component List</h5>
                  </div>

                 <!-- PD Table -->
                <div class="table-responsive mt-4">
                  <DataTable :columns="pdColumns" :data="pdData" class="table table-bordered table-striped"
                    :options="{ responsive: true, pageLength: 5, lengthChange: false }">
                    <template #thead>
                      <thead>
                        <tr>
                          <th>S. No.</th>
                          <th>Type</th>
                          <th>Name</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                    </template>

                    <template #row="{ row, rowIndex }">
                      <tr>
                         <td>{{ row.serial }}</td>
                        <td>{{ row.component }}</td>
                        <td>{{ row.name }}</td>
                        <td>
                          <a class="me-2" @click="deleteRow(row.id)">
                            <i class="fas fa-trash"></i>
                          </a>
                        </td>
                      </tr>
                    </template>
                  </DataTable>
                </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 3: SL Component List -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section3 }" type="button" @click="toggleAccordion('section3')" aria-expanded="false" aria-controls="collapseThree">
                      <i class="fas fa-layer-group me-2"></i>
                      SL Component List
                    </button>
                  </h2>
                  <div id="collapseThree" class="accordion-collapse" :class="{ 'show': accordionStates.section3 }" aria-labelledby="headingThree" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section3">
                      <div class="card">
                        <div class="card-body">
                          <div class="bg-primary text-white px-3 py-2 rounded mb-3">
                            <h5 class="mb-0">SL Component List</h5>
                </div>

               <!-- SL Table -->
                <div class="table-responsive mt-4">
                  <DataTable :columns="slColumns" :data="slData" class="table table-bordered table-striped"
                    :options="{ responsive: true, pageLength: 5, lengthChange: false }">
                    <template #thead>
                      <thead>
                        <tr>
                          <th>S. No.</th>
                          <th>Type</th>
                          <th>State</th>
                          <th>PD</th>
                          <th>Name</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                    </template>

                    <template #row="{ row, rowIndex }">
                      <tr>
                         <td>{{ row.serial }}</td>
                        <td>{{ row.component }}</td>
                <td>{{ row.state?.name || '-' }}</td>

                        <td>{{ row.name }}</td>
                        <td>
                          <a class="me-2" @click="deleteRow(row.id)">
                            <i class="fas fa-trash"></i>
                          </a>
                        </td>
                      </tr>
                    </template>
                  </DataTable>
                </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 4: SLS Upload and Preview -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section4 }" type="button" @click="toggleAccordion('section4')" aria-expanded="false" aria-controls="collapseFour">
                      <i class="fas fa-upload me-2"></i>
                      SLS Upload and Preview
                    </button>
                  </h2>
                  <div id="collapseFour" class="accordion-collapse" :class="{ 'show': accordionStates.section4 }" aria-labelledby="headingFour" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section4">
                      <div class="card">
                        <div class="card-body">
                          <div class="bg-primary text-white px-3 py-2 rounded mb-3">
                            <h5 class="mb-0">SLS Upload and Preview</h5>
                </div>

                          <!-- Upload Section -->
                          <div class="row mb-4">
                            <div class="col-md-6">
                              <div class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                <div class="mb-3">
                                  <i class="fas fa-file-excel fa-3x text-success"></i>
                                </div>
                                                                 <h6 class="mb-2">Upload SLS File</h6>
                                 <p class="text-muted mb-3">Drag and drop your Excel or CSV file here or click to browse</p>
                                                                 <input 
                                   type="file" 
                                   ref="fileInput" 
                                   @change="handleFileUpload" 
                                   accept=".xlsx,.xls,.csv" 
                                   class="form-control"
                                   style="display: none;"
                                 />
                                <button @click="$refs.fileInput.click()" class="btn btn-primary" :disabled="isUploading">
                                  <i class="fas fa-upload me-2"></i>
                                  {{ isUploading ? 'Processing...' : 'Choose File' }}
                                </button>
                                <div v-if="selectedFile" class="mt-2">
                                  <small class="text-success">
                                    <i class="fas fa-check me-1"></i>{{ selectedFile.name }}
                                  </small>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="upload-info">
                                <h6>Upload Instructions:</h6>
                                                                 <ul class="list-unstyled">
                                   <li><i class="fas fa-info-circle text-info me-2"></i>File should be in Excel (.xlsx, .xls) or CSV (.csv) format</li>
                                   <li><i class="fas fa-info-circle text-info me-2"></i>First row should contain column headers</li>
                                   <li><i class="fas fa-info-circle text-info me-2"></i>Required columns: SLS Code, SLS Name, State Name, SG Account, Sharing Pattern(Centre), Sharing Pattern(State)</li>
                                   <li><i class="fas fa-info-circle text-info me-2"></i>Maximum file size: 10MB</li>
                                 </ul>
                              </div>
                            </div>
                          </div>

                          <!-- Preview Table -->
                          <div v-if="slsPreviewData.length > 0" class="table-responsive mt-4">
                                                          <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Preview Data ({{ slsPreviewData.length }} rows)</h6>
                                <div>
                                  <button @click="saveSLSData" class="btn btn-success me-2" :disabled="!slsPreviewData.length || isSaving">
                                    <i class="fas fa-save me-2"></i>
                                    {{ isSaving ? 'Saving...' : 'Save Data' }}
                                  </button>
                                  <button @click="clearSLSData" class="btn btn-secondary" :disabled="isSaving">
                                    <i class="fas fa-trash me-2"></i>Clear
                                  </button>
                                </div>
                              </div>
                            <table class="table table-bordered table-striped">
                              <thead class="table-primary">
                                <tr>
                                  <th>SLS Code</th>
                                  <th>SLS Name</th>
                                  <th>State Name</th>
                                  <th>SG Account</th>
                                  <th>Sharing Pattern(Centre)</th>
                                  <th>Sharing Pattern(State)</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(row, index) in slsPreviewData" :key="index">
                                  <td>{{ row.slsCode }}</td>
                                  <td>{{ row.slsName }}</td>
                                  <td>{{ row.stateName }}</td>
                                  <td>{{ row.sgAccount }}</td>
                                  <td>{{ row.sharingPatternCentre }}</td>
                                  <td>{{ row.sharingPatternState }}</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>

                          <!-- No Data Message -->
                          <div v-else class="text-center py-4">
                            <i class="fas fa-file-excel fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No data to preview. Please upload an Excel file.</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div> <!-- End Accordion Container -->
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
import { ref, watch, onMounted,computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net-bs5'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import 'datatables.net-bs5/css/dataTables.bootstrap5.css'
// Note: For production, install and import SheetJS: npm install xlsx
// import * as XLSX from 'xlsx'

DataTable.use(DataTablesCore)
const pdColumns = [
  { title: 'S. No.', data: 'serial',width: '1%' },

  { title: 'Type', data: 'component',width: '3%' },
  { title: 'Name', data: 'name' },
  
]

const slColumns = [

  { title: 'S. No.', data: 'serial',width: '1%' },
  { title: 'Type', data: 'component',width: '3%' },
  { title: 'State', data: 'state.name',width: '5%' },
  { title: 'PD', data: 'slsPD' },
  { title: 'Name', data: 'name' },
  
]  

const pdData = computed(() =>
  savedData.value
    .filter(i => i.component === 'PD')
    .map((item, index) => ({ ...item, serial: index + 1 }))
)

const slData = computed(() =>
  savedData.value
    .filter(i => i.component === 'SL')
    .map((item, index) => ({
      ...item,
      serial: index + 1
    }))
)



const savedData = ref([]);

const selectedComponent = ref('')

// Accordion state management
const accordionStates = ref({
  section1: true,  // First section expanded by default
  section2: false, // Second section collapsed by default
  section3: false, // Third section collapsed by default
  section4: false  // Fourth section collapsed by default
})

const toggleAccordion = (section) => {
  accordionStates.value[section] = !accordionStates.value[section]
}

// SLS Upload functionality
const fileInput = ref(null)
const selectedFile = ref(null)
const slsPreviewData = ref([])
const isUploading = ref(false)
const isSaving = ref(false)

const handleFileUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validate file type
  const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv']
  if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv')) {
    alert('Please upload an Excel file (.xlsx, .xls) or CSV file (.csv)')
    return
  }

  // Validate file size (10MB)
  if (file.size > 10 * 1024 * 1024) {
    alert('File size should be less than 10MB')
    return
  }

  selectedFile.value = file
  isUploading.value = true
  
  try {
    await parseExcelFile(file)
  } finally {
    isUploading.value = false
  }
}

const parseExcelFile = async (file) => {
  try {
    // Create FormData to send file to backend
    const formData = new FormData()
    formData.append('file', file)
    
    // Show loading state
    const loadingMessage = 'Processing Excel file...'
    console.log(loadingMessage)
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    console.log('CSRF Token:', csrfToken ? 'Present' : 'Missing')
    
    // Send file to backend for processing
    const response = await fetch('/sls-upload', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    
    console.log('Response status:', response.status)
    console.log('Response headers:', Object.fromEntries(response.headers.entries()))
    
    // Check if response is JSON
    const contentType = response.headers.get('content-type')
    if (!contentType || !contentType.includes('application/json')) {
      const textResponse = await response.text()
      console.error('Non-JSON response:', textResponse.substring(0, 500))
      throw new Error('Server returned non-JSON response')
    }
    
    const result = await response.json()
    console.log('Response data:', result)
    
    if (result.success) {
      // Transform backend data to match frontend format
      const transformedData = result.data.structured_data.map(item => ({
        slsCode: item.sls_code || '',
        slsName: item.sls_name || '',
        stateName: item.state_name || '',
        sgAccount: item.sg_account || '',
        sharingPatternCentre: item.sharing_pattern_centre || '',
        sharingPatternState: item.sharing_pattern_state || ''
      }))
      
      slsPreviewData.value = transformedData
      console.log(`Successfully processed ${transformedData.length} rows from Excel file`)
      
      // Show success message
      alert(`Successfully processed ${transformedData.length} records from your file!`)
    } else {
      throw new Error(result.message || 'Failed to process Excel file')
    }
    
  } catch (error) {
    console.error('Error processing Excel file:', error)
    alert(`Error processing Excel file: ${error.message}`)
    
    // Fallback to sample data if backend processing fails
    console.log('Using fallback sample data...')
    const fallbackData = [
      { slsCode: 'AP17', slsName: 'National Food Security', stateName: 'ANDHRA PRADESH', sgAccount: '01604901079', sharingPatternCentre: '60', sharingPatternState: '40' },
      { slsCode: 'AP24', slsName: 'Sub Mission on Agriculture', stateName: 'ANDHRA PRADESH', sgAccount: '01604901081', sharingPatternCentre: '90', sharingPatternState: '10' },
      { slsCode: 'AR18', slsName: 'ARP_NATIONAL MISSION ON', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601161', sharingPatternCentre: '100', sharingPatternState: '0' },
      { slsCode: 'AS10', slsName: 'AS - NFSM-National Food', stateName: 'ASSAM', sgAccount: '01585401129', sharingPatternCentre: '60', sharingPatternState: '40' }
    ]
    slsPreviewData.value = fallbackData
  }
}

const saveSLSData = async () => {
  if (slsPreviewData.value.length === 0) {
    alert('No data to save')
    return
  }

  isSaving.value = true

  try {
    // Send data to backend for saving
    const response = await fetch('/sls-save', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        slsData: slsPreviewData.value
      })
    })

    const result = await response.json()

    if (result.success) {
      alert(`Successfully saved ${slsPreviewData.value.length} SLS records!`)
      clearSLSData()
    } else {
      throw new Error(result.message || 'Failed to save SLS data')
    }
  } catch (error) {
    console.error('Error saving SLS data:', error)
    alert(`Error saving SLS data: ${error.message}`)
  } finally {
    isSaving.value = false
  }
}

const clearSLSData = () => {
  slsPreviewData.value = []
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
const fetchSavedData = async () => {
  try {
    const res = await fetch('/pd-sls-list');
    if (res.ok) {
      savedData.value = await res.json();
    } else {
      console.error('Failed to load saved data');
    }
  } catch (err) {
    console.error('Error loading saved data:', err);
  }
};
const formRows = ref([
  {
    state: '',
    pdComponent: '',
    slsPD: '',
    slsId: ''
  }
])

const states = ref([])

onMounted(async () => {
  fetchSavedData();
  
  try {
    const response = await fetch('/api/states')
    if (response.ok) {
      states.value = await response.json()
    } else {
      console.error('Failed to fetch states:', response.statusText)
    }
  } catch (error) {
    console.error('Error fetching states:', error)
  }
})

const addRow = () => {
  formRows.value.push({
    state: '',
    pdComponent: '',
    slsPD: '',
    slsId: ''
  })
}

watch(selectedComponent, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    formRows.value = [
      {
        state: '',
        pdComponent: '',
        slsPD: '',
        slsId: ''
      }
    ]
  }
})
const deleteRow = (id) => {
  if (confirm('Are you sure you want to delete this item?')) {
    router.delete(route('pd-sls.destroy', id), {
      onSuccess: () => {
        fetchSavedData()
        alert('Deleted successfully!')
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        alert('Delete failed')
      }
    })
  }
}

const submit = () => {
  const payload = {
    component: selectedComponent.value,
    comValue: [],
    status: 1
  }

  formRows.value.forEach(row => {
    payload.comValue.push({
      state: selectedComponent.value === 'PD' ? 0 : row.state,
      name: selectedComponent.value === 'PD' ? row.pdComponent : row.slsId,
      slsPD: selectedComponent.value === 'PD' ? null : row.slsPD // Send slsPD only for SL type
    })
  })

  router.post(route('pd-sls.store'), payload, {
    onSuccess: () => {
      selectedComponent.value = ''
      formRows.value = [
        { state: '', pdComponent: '', slsId: '', slsPD: '' }
      ]
      fetchSavedData()
      alert('Saved successfully!')
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed')
    }
  })
}



</script>

<style scoped>
.accordion-collapse {
  transition: all 0.3s ease;
}

.accordion-collapse.show {
  display: block;
}

.accordion-collapse:not(.show) {
  display: none;
}

.accordion-button {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  color: #212529;
  text-align: left;
  background-color: #fff;
  border: 0;
  border-radius: 0;
  overflow-anchor: none;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, border-radius 0.15s ease;
}

.accordion-button:not(.collapsed) {
  color: #0c63e4;
  background-color: #e7f1ff;
  box-shadow: inset 0 -1px 0 #dee2e6;
}

.accordion-button.collapsed {
  color: #212529;
  background-color: #fff;
  box-shadow: inset 0 -1px 0 #dee2e6;
}

.accordion-button::after {
  flex-shrink: 0;
  width: 1.25rem;
  height: 1.25rem;
  margin-left: auto;
  content: "";
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23212529'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-size: 1.25rem;
  transition: transform 0.15s ease;
}

.accordion-button:not(.collapsed)::after {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230c63e4'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
  transform: rotate(-180deg);
}

.accordion-body {
  padding: 1rem 1.25rem;
}

.accordion-item {
  background-color: #fff;
  border: 1px solid #dee2e6;
}

.accordion-item:not(:first-of-type) {
  border-top: 0;
}

.accordion-item:first-of-type {
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
}

.accordion-item:last-of-type {
  border-bottom-left-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
}

/* Upload area styles */
.upload-area {
  border: 2px dashed #dee2e6;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  transition: all 0.3s ease;
}

.upload-area:hover {
  border-color: #0d6efd;
  background-color: #f8f9fa;
}

.upload-info {
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 0.5rem;
  border-left: 4px solid #0d6efd;
}

.upload-info ul li {
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}
</style>


