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
              <!-- <li class="nav-item"><a href="#">States/UTs - PD/Component - SLS IDs Mapping</a></li> -->
              <li class="nav-item"><a href="#">PD Component / SLS</a></li>
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
                      <!-- State-wise list of PD/Component and SLS -->
                      Add PD Component / SLS
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
                                      <option value="SL">SLS</option>
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
                                  <th v-if="selectedComponent === 'SL'">SLS Code</th>
                                  <th v-if="selectedComponent === 'SL'">SLS Name</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(row, index) in formRows" :key="index">
                                  <td v-if="selectedComponent === 'SL'">
                                  <select v-model="row.slsPD" class="form-select">
                                      <option value="">--- Select PD ---</option>
                                      <option
                                        v-for="item in pdComponentsForDropdown"
                                        :key="item.division_id"
                                        :value="item.division_name"
                                      >
                                        {{ item.division_name }}
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
                                    <input v-model="row.slsId" type="text" class="form-control" placeholder="Enter Code" />
                                  </td>
                                  <td v-if="selectedComponent === 'SL'">
                                    <input v-model="row.slsName" type="text" class="form-control" placeholder="Enter SLS Name" />
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

                <!-- Section 2: SLS Upload and Preview -->
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
                                  <i class="fas fa-file-excel fa-3x text-success me-2"></i>
                                  <!-- <i class="fas fa-file-pdf fa-3x text-danger"></i> -->
                                </div>
                                <!--<h6 class="mb-2">Upload SLS Excel/PDF File</h6>-->
                                <h6 class="mb-2">Upload SLS Excel File</h6>
                                <!-- <p class="text-muted mb-3">Drag and drop your Excel or PDF file here or click to browse</p> -->
                                <p class="text-muted mb-3">Drag and drop your Excel file here or click to browse</p>
                                <input
                                  type="file"
                                  ref="fileInput"
                                  @change="handleFileUpload"
                                  accept=".xlsx,.xls"
                                  class="form-control"
                                  style="display: none;"
                                />
                                <button @click="$refs.fileInput.click()" class="btn btn-primary" :disabled="isUploading">
                                  <i class="fas fa-upload me-2"></i>{{ isUploading ? 'Uploading...' : 'Choose File' }}
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
                                  <!-- <li><i class="fas fa-info-circle text-info me-2"></i>File should be in Excel format (.xlsx, .xls) or PDF format (.pdf)</li> -->
                                  <li><i class="fas fa-info-circle text-info me-2"></i>File should be in Excel format (.xlsx, .xls)</li>
                                  <li><i class="fas fa-info-circle text-info me-2"></i>For Excel: First row should contain column headers</li>
                                  <!-- <li><i class="fas fa-info-circle text-info me-2"></i>For PDF: Data should be in structured format with SLS codes and details</li> -->
                                  <!-- <li><i class="fas fa-info-circle text-info me-2"></i>Required columns: Controller, Centrally Sponsored Scheme (CSS), State Name, State Linked Scheme (SLS), SG Account, Sharing Pattern</li> -->
                                  <li><i class="fas fa-info-circle text-info me-2"></i>SLS column format: CODE - NAME (e.g., AP17 - National Food Security)</li>
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
                                  <i class="fas fa-save me-2"></i>{{ isSaving ? 'Saving...' : 'Proceed & Save' }}
                                </button>
                                <button @click="clearSLSData" class="btn btn-secondary">
                                  <i class="fas fa-trash me-2"></i>Clear
                                </button>
                              </div>
                            </div>
                            
                            <!-- Pagination Info and Controls -->
                            <div v-if="slsPreviewData.length > 0" class="d-flex justify-content-between align-items-center mb-3">
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
                                  Showing {{ startIndex }} to {{ endIndex }} of {{ slsPreviewData.length }} entries
                                </span>
                              </div>
                            </div>
                            
                            <table class="table table-bordered table-striped">
                              <thead class="table-primary">
                                <tr>
                                  <th>SLS Code</th>
                                  <th>SLS Name</th>
                                  <th>Full SLS Name</th>
                                  <th>State Name</th>
                                  <!-- <th>SG Account</th> -->
                                  <th>Sharing Pattern(Centre)</th>
                                  <th>Sharing Pattern(State)</th>
                                  <!-- <th>Controller</th>
                                  <th>CSS</th>
                                  <th>CG Account</th> -->
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(row, index) in paginatedSlsPreviewData" :key="index">
                                  <td>{{ row.slsCode }}</td>
                                  <td>{{ row.slsName }}</td>
                                  <td>{{ row.slsCode }} - {{ row.slsName }}</td>
                                  <td>{{ row.stateName }}</td>
                                  <!-- <td>{{ row.sgAccount }}</td> -->
                                  <td>{{ row.sharingPatternCentre }}</td>
                                  <td>{{ row.sharingPatternState }}</td>
                                  <!-- <td>{{ row.controller }}</td>
                                  <td>{{ row.css }}</td>
                                  <td>{{ row.cgAccount }}</td> -->
                                </tr>
                              </tbody>
                            </table>
                            
                            <!-- Pagination Controls -->
                            <div v-if="slsPreviewData.length > 0 && totalPages > 1" class="d-flex justify-content-center mt-3">
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

                          <!-- No Data Message -->
                          <div v-else class="text-center py-4">
                            <i class="fas fa-file-excel fa-3x text-muted mb-3 me-2"></i>
                            <!-- <i class="fas fa-file-pdf fa-3x text-muted mb-3"></i> -->
                            <!-- <p class="text-muted">No data to preview. Please upload an Excel or PDF file.</p> -->
                            <p class="text-muted">No data to preview. Please upload an Excel file.</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 3: PD Component List -->
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
                           <th>PD Name</th>
                         </tr>
                       </thead>
                     </template>

                     <template #row="{ row, rowIndex }">
                       <tr>
                          <td>{{ row.serial }}</td>
                         <td>{{ row.division_name }}</td>
                       </tr>
                     </template>
                  </DataTable>
                </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 4: SL Component List -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section3 }" type="button" @click="toggleAccordion('section3')" aria-expanded="false" aria-controls="collapseThree">
                      <i class="fas fa-layer-group me-2"></i>
                      SLS Component List
                    </button>
                  </h2>
                  <div id="collapseThree" class="accordion-collapse" :class="{ 'show': accordionStates.section3 }" aria-labelledby="headingThree" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section3">
                      <div class="card">
                        <div class="card-body">
                          <div class="bg-primary text-white px-3 py-2 rounded mb-3">
                            <h5 class="mb-0">SLS Component List</h5>
                </div>

               <!-- SL Table -->
                <div class="table-responsive mt-4">
                  <DataTable :columns="slColumns" :data="slData" class="table table-bordered table-striped"
                    :options="{ responsive: true, pageLength: 5, lengthChange: false }">
                    <template #thead>
                      <thead>
                        <tr>
                          <th>SLS Code</th>
                          <th>SLS Name</th>
                          <th>Full SLS Name</th>
                          <th>PD Name</th>
                          <th>State Name</th>
                          <th>Sharing Pattern(Centre)</th>
                          <th>Sharing Pattern(State)</th>
                        </tr>
                      </thead>
                    </template>

                    <template #row="{ row, rowIndex }">
                      <tr>
                        <td>{{ row.sls_code || '-' }}</td>
                        <td>{{ row.name || '-' }}</td>
                        <td>{{ row.full_sls_name || '-' }}</td>
                        <td>{{ row.slsPD || '-' }}</td>
                        <td>{{ row.state?.name || '-' }}</td>
                        <td>{{ row.sharing_patter_center !== null && row.sharing_patter_center !== undefined ? row.sharing_patter_center : '0' }}</td>
                        <td>{{ row.sharing_patter_state !== null && row.sharing_patter_state !== undefined ? row.sharing_patter_state : '0' }}</td>
                      </tr>
                    </template>
                  </DataTable>
                </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>


                <!-- Section 5: PD Components and SLS Mapping -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section5 }" type="button" @click="toggleAccordionAndLoadSLS('section5')" aria-expanded="true" aria-controls="collapseOne">
                      <i class="fas fa-map-marker-alt me-2"></i>
                      <!-- State-wise list of PD/Component and SLS -->
                      PD Components and SLS Mapping
                    </button>
                  </h2>
                  <div id="collapseOne" class="accordion-collapse" :class="{ 'show': accordionStates.section5 }" aria-labelledby="headingOne" data-bs-parent="#stateUTsMapAccordion">
                    <div class="accordion-body" v-show="accordionStates.section5">
                      <div class="card">
                        <div class="card-body">
                          <!-- First Table: Static with Component Dropdown -->
                          

                          <!-- Second Table: Conditionally rendered -->
                          <div class="table-responsive mt-1" >
                            <table class="table table-bordered table-head-bg-primary">
                              <thead>
                                <tr>
                                  <th>PD List</th>
                                  <th>SLS List</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(row, index) in formRows" :key="index">
                                  <td>
                                  <select v-model="row.slsPD" class="form-select">
                                      <option value="">--- Select PD ---</option>
                                      <option
                                        v-for="item in pdComponentsForDropdown"
                                        :key="item.division_id"
                                        :value="item.division_name"
                                      >
                                        {{ item.division_name }}
                                      </option>

                                    </select>

                                  </td>

                                  <td>
                                    <!-- SLS List Dropdown -->
                                    <!-- <select v-model="row.slsList" class="form-select">
                                      <option value="">--- Select SLS ---</option>
                                      <option
                                        v-for="sls in slsDataForDropdown"
                                        :key="sls.id"
                                        :value="sls.sls_code"
                                      >
                                        {{ sls.sls_code }} - {{ sls.name }}
                                      </option>
                                    </select> -->



                                    <div
                                     
                                      class="checkbox-list"
                                      style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;"
                                    >
                                            <!-- {{ slsDataForDropdown }}                            -->
                                       <!-- Show PDs when entityType is Agency -->
                                      <div>
                                        <div v-for="sls in slsDataForDropdown" :key="sls.id"  class="form-check">
                                          <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :id="sls.id"
                                            :value="sls.id"
                                            v-model="selectedEntities"
                                          />
                                          <label class="form-check-label" :for="sls.id">
                                            {{ sls.full_sls_name || sls.name }}
                                          </label>
                                        </div>
                                      </div>
                                    </div>
                                  </td>

                                  <!-- <td v-if="selectedComponent === 'SL'">
                                    <input v-model="row.slsId" type="text" class="form-control" placeholder="Enter Code" />
                                  </td>
                                  <td v-if="selectedComponent === 'SL'">
                                    <input v-model="row.slsName" type="text" class="form-control" placeholder="Enter SLS Name" />
                                  </td> -->
                                </tr>
                              </tbody>
                            </table>

                            <!-- Add Row -->
                            <!-- <div class="col-md-12 col-lg-12">
                              <button class="btn btn-primary me-1 mb-4" @click="addRow">+ Add New</button>
                            </div> -->
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

const getEntityNames = (ids) => {
  
    return ids
      .map((id) => {
        const entity = programDivisions.value.find((pd) => pd.division_id === id);
        return entity ? entity.division_name : '';
      })
      .filter((name) => name);

};

DataTable.use(DataTablesCore)
const pdColumns = [
  { title: 'S. No.', data: 'serial',width: '1%' },
  { title: 'Division Name', data: 'division_name' }
]

const slColumns = [
  { title: 'SLS Code', data: 'sls_code' },
  { title: 'SLS Name', data: 'name' },
  { title: 'Full SLS Name', data: 'full_sls_name' },
  { title: 'PD Name', data: 'slsPD' },
  { title: 'State Name', data: 'state.name' },
  { title: 'Sharing Pattern(Centre)', data: 'sharing_patter_center' },
  { title: 'Sharing Pattern(State)', data: 'sharing_patter_state' }
]  

const pdData = computed(() =>
  pdComponentsData.value
    .map((item, index) => ({ ...item, serial: index + 1 }))
)

const slData = computed(() =>
  savedData.value
    .filter(i => i.component === 'SL' || i.sls_code) // Include records with sls_code (from Excel uploads)
)

// Computed property to get full SLS name for form rows
const getFullSlsName = (row) => {
  if (row.slsId && row.slsName) {
    return `${row.slsId} - ${row.slsName}`;
  }
  return '';
}


const savedData = ref([]);
const pdComponentsData = ref([]);
const slsDataForDropdown = ref([]);
const programDivisions = ref([]);
const selectedEntities = ref([]);

const selectedComponent = ref('')

// Accordion state management
const accordionStates = ref({
  section1: true,  // First section expanded by default
  section2: false, // Second section collapsed by default
  section3: false, // Third section collapsed by default
  section4: false,  // Fourth section collapsed by default
  section5: false  // Fifth section collapsed by default
})

const toggleAccordion = (section) => {
  accordionStates.value[section] = !accordionStates.value[section]
}

const toggleAccordionAndLoadSLS = (section) => {
  accordionStates.value[section] = !accordionStates.value[section]
  
  // If section5 is being expanded, load SLS data
  if (section === 'section5' && accordionStates.value[section]) {
    fetchSLSDataForDropdown()
  }
}

// SLS Upload functionality
const fileInput = ref(null)
const selectedFile = ref(null)
const slsPreviewData = ref([])
const isUploading = ref(false)
const isSaving = ref(false)

// Pagination variables for SLS Preview Data
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Pagination computed properties for SLS Preview Data
const totalPages = computed(() => {
  return Math.ceil(slsPreviewData.value.length / itemsPerPage.value)
})

const paginatedSlsPreviewData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return slsPreviewData.value.slice(start, end)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const endIndex = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, slsPreviewData.value.length)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages = []
  
  if (total <= 7) {
    // If total pages is 7 or less, show all pages
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)
    
    if (current > 3) {
      pages.push('...')
    }
    
    // Show pages around current page
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      if (i !== 1 && i !== total) {
        pages.push(i)
      }
    }
    
    if (current < total - 2) {
      pages.push('...')
    }
    
    // Always show last page
    if (total > 1) {
      pages.push(total)
    }
  }
  
  return pages
})

// Pagination functions for SLS Preview Data
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
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
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validate file type
  const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/pdf']
  if (!allowedTypes.includes(file.type)) {
    alert('Please upload an Excel file (.xlsx or .xls)')
    // alert('Please upload an Excel file (.xlsx or .xls) or a PDF file (.pdf)')
    return
  }

  // Validate file size (10MB)
  if (file.size > 10 * 1024 * 1024) {
    alert('File size should be less than 10MB')
    return
  }

  selectedFile.value = file
  parseFile(file)
}



const parseFile = (file) => {
  isUploading.value = true
  const formData = new FormData()
  formData.append('file', file)

  // Get CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  
  if (!csrfToken) {
    isUploading.value = false
    alert('CSRF token not found. Please refresh the page and try again.')
    return
  }

  // Determine the endpoint based on file type
  const endpoint = file.type === 'application/pdf' ? '/pd-sls/upload-pdf' : '/pd-sls/upload-excel'

  fetch(endpoint, {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => {
    if (!response.ok) {
      if (response.status === 419) {
        throw new Error('CSRF token mismatch. Please refresh the page and try again.')
      }
      return response.json().then(errorData => {
        throw new Error(JSON.stringify(errorData));
      });
    }
    return response.json();
  })
  .then(data => {
    isUploading.value = false
    if (data.success) {
      slsPreviewData.value = data.data
      // Reset pagination to first page when new data is loaded
      currentPage.value = 1
      const fileType = file.type === 'application/pdf' ? 'PDF' : 'Excel'
      let message = `Successfully parsed ${data.totalRows} rows from ${fileType} file`
      
      // Show warnings if any
      if (data.warnings && data.warnings.length > 0) {
        message += `\n\nWarnings:\n${data.warnings.slice(0, 5).join('\n')}`
        if (data.warnings.length > 5) {
          message += `\n... and ${data.warnings.length - 5} more warnings`
        }
      }
      
      alert(message)
    } else {
      throw new Error(data.message || 'Error parsing file')
    }
  })
  .catch(error => {
    isUploading.value = false
    console.error('Error uploading file:', error)
    
    // Try to parse error message
    let errorMessage = 'Error uploading file. Please try again.'
    try {
      const errorData = JSON.parse(error.message)
      if (errorData.message) {
        errorMessage = errorData.message
      }
      if (errorData.errors && errorData.errors.length > 0) {
        errorMessage += '\n\nFirst few errors:\n' + errorData.errors.slice(0, 5).join('\n')
        if (errorData.errors.length > 5) {
          errorMessage += `\n... and ${errorData.errors.length - 5} more errors`
        }
      }
    } catch (e) {
      // If parsing fails, use the original error message
      errorMessage = error.message || errorMessage
    }
    
    alert(errorMessage)
  })
}

const saveSLSData = () => {
  if (slsPreviewData.value.length === 0) {
    alert('No data to save')
    return
  }

  isSaving.value = true

  // Get CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  
  if (!csrfToken) {
    isSaving.value = false
    alert('CSRF token not found. Please refresh the page and try again.')
    return
  }

  // Prepare data for saving
  const dataToSave = slsPreviewData.value.map(item => ({
    slsCode: item.slsCode,
    slsName: item.slsName,
    stateName: item.stateName,
    sgAccount: item.sgAccount,
    sharingPatternCentre: item.sharingPatternCentre,
    sharingPatternState: item.sharingPatternState
  }))



  fetch('/pd-sls/save-sls-data', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ data: dataToSave })
  })
  .then(response => {
    if (!response.ok) {
      if (response.status === 419) {
        throw new Error('CSRF token mismatch. Please refresh the page and try again.')
      }
      return response.json().then(errorData => {
        throw new Error(JSON.stringify(errorData));
      });
    }
    return response.json();
  })
  .then(data => {
    isSaving.value = false
    if (data.success) {
      alert(`Successfully saved ${data.savedCount} SLS records!`)
      clearSLSData()
      fetchSavedData() // Refresh the saved data list
    } else {
      let errorMessage = 'Error saving data: ' + data.message
      if (data.errors && data.errors.length > 0) {
        errorMessage += '\n\nFirst few errors:\n' + data.errors.slice(0, 5).join('\n')
        if (data.errors.length > 5) {
          errorMessage += `\n... and ${data.errors.length - 5} more errors`
        }
      }
      alert(errorMessage)
      console.error('Save errors:', data.errors)
    }
  })
  .catch(error => {
    isSaving.value = false
    console.error('Error saving data:', error)
    alert('Error saving data. Please try again.')
  })
}

const clearSLSData = () => {
  slsPreviewData.value = []
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  // Reset pagination
  currentPage.value = 1
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

const fetchPDComponentsData = async () => {
  try {
    const res = await fetch('/pd-components-list');
    if (res.ok) {
      pdComponentsData.value = await res.json();
    } else {
      console.error('Failed to load PD components data');
    }
  } catch (err) {
    console.error('Error loading PD components data:', err);
  }
};

const fetchPDComponentsForDropdown = async () => {
  try {
    const res = await fetch('/pd-components-dropdown');
    if (res.ok) {
      pdComponentsForDropdown.value = await res.json();
    } else {
      console.error('Failed to load PD components for dropdown');
    }
  } catch (err) {
    console.error('Error loading PD components for dropdown:', err);
  }
};

const fetchSLSDataForDropdown = async () => {
  try {
    const res = await fetch('/pd-sls-list');
    console.log(res);
    // // return false;
    if (res.ok) {
      const data = await res.json();
      // Filter only SLS records (those with sls_code)
      slsDataForDropdown.value = data.filter(item => item.sls_code);
      console.log(slsDataForDropdown.value);
    } else {
      console.error('Failed to load SLS data for dropdown');
    }
  } catch (err) {
    console.error('Error loading SLS data for dropdown:', err);
  }
};

const fetchProgramDivisions = async () => {
  try {
    const res = await fetch('/pd-components-dropdown');
    if (res.ok) {
      programDivisions.value = await res.json();
    } else {
      console.error('Failed to load program divisions');
    }
  } catch (err) {
    console.error('Error loading program divisions:', err);
  }
};
const formRows = ref([
  {
    state: '',
    pdComponent: '',
    slsPD: '',
    slsId: '',
    slsList: ''
  }
])

const states = ref([])
const pdComponentsForDropdown = ref([])

onMounted(async () => {
  fetchSavedData();
  fetchPDComponentsData();
  fetchPDComponentsForDropdown();
  fetchSLSDataForDropdown();
  fetchProgramDivisions();
  
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
    slsId: '',
    slsName: '',
    slsList: ''
  })
}

watch(selectedComponent, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    formRows.value = [
      {
        state: '',
        pdComponent: '',
        slsPD: '',
        slsId: '',
        slsName: '',
        slsList: ''
      }
    ]
    
    // If SLS ID is selected, fetch PD components for dropdown
    if (newValue === 'SL') {
      fetchPDComponentsForDropdown();
    }
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
  // Check if we're in the PD Components and SLS Mapping section
  if (accordionStates.value.section5) {
    // submitPDSLSMapping()
    submitPDSLSMappinglist()
  } else {
    submitRegularForm()
  }
}
const submitPDSLSMapping = () => {
  // Validate that all required fields are filled
  const validRows = formRows.value.filter(row => 
    row.slsPD && row.slsList
  )

  console.log( validRows)
  if (validRows.length === 0) {
    alert('Please fill in all required fields (PD and SLS)')
    return
  }

  console.log('Valid rows for mapping:', validRows)

  const payload = {
    mappings: validRows.map(row => ({
      pd_name: row.slsPD,
      // state_id: row.state,
      sls_code: row.slsList
    }))
  }
console.log( "===============payload===============")
console.log( payload)   
  // Get CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  
  if (!csrfToken) {
    alert('CSRF token not found. Please refresh the page and try again.')
    return
  }

  fetch('/pd-sls/update-mappings', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(payload)
  })
  .then(response => {
    console.log( "===============response===============")
    console.log( response);
    if (!response.ok) {
      if (response.status === 419) {
        throw new Error('CSRF token mismatch. Please refresh the page and try again.')
      }
      return response.json().then(errorData => {
        throw new Error(JSON.stringify(errorData));
      });
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      alert(`Successfully updated ${data.updatedCount} mappings!`)
      // Reset form
      formRows.value = [
        { state: '', pdComponent: '', slsId: '', slsPD: '', slsName: '', slsList: '' }
      ]
      fetchSavedData()
      fetchSLSDataForDropdown()
    } else {
      let errorMessage = 'Error updating mappings: ' + data.message
      if (data.errors && data.errors.length > 0) {
        errorMessage += '\n\nFirst few errors:\n' + data.errors.slice(0, 5).join('\n')
        if (data.errors.length > 5) {
          errorMessage += `\n... and ${data.errors.length - 5} more errors`
        }
      }
      alert(errorMessage)
    }
  })
  .catch(error => {
    console.error('Error updating mappings:', error)
    alert('Error updating mappings. Please try again.')
  })
}

const submitPDSLSMappinglist = () => {
  console.log( "===============formRows===============")
  console.log(formRows.value);
  
  // Get selected SLS IDs from checkboxes
  const selectedSLSIds = selectedEntities.value;
  
  if (!formRows.value[0].slsPD) {
    alert('Please select a PD')
    return
  }
  
  if (selectedSLSIds.length === 0) {
    alert('Please select at least one SLS')
    return
  }

  console.log('Selected PD:', formRows.value[0].slsPD)
  console.log('Selected SLS IDs:', selectedSLSIds)

  const payload = {
    mappings: selectedSLSIds.map(slsId => ({
      pd_name: formRows.value[0].slsPD,
      sls_id: slsId
    }))
  }

  // Get CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  
  if (!csrfToken) {
    alert('CSRF token not found. Please refresh the page and try again.')
    return
  }

  fetch('/pd-sls/update-mappings', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(payload)
  })
  .then(response => {
    if (!response.ok) {
      if (response.status === 419) {
        throw new Error('CSRF token mismatch. Please refresh the page and try again.')
      }
      return response.json().then(errorData => {
        throw new Error(JSON.stringify(errorData));
      });
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      alert(`Successfully updated ${data.updatedCount} mappings!`)
      // Reset form
      formRows.value = [
        { state: '', pdComponent: '', slsId: '', slsPD: '', slsName: '', slsList: '' }
      ]
      fetchSavedData()
      fetchSLSDataForDropdown()
    } else {
      let errorMessage = 'Error updating mappings: ' + data.message
      if (data.errors && data.errors.length > 0) {
        errorMessage += '\n\nFirst few errors:\n' + data.errors.slice(0, 5).join('\n')
        if (data.errors.length > 5) {
          errorMessage += `\n... and ${data.errors.length - 5} more errors`
        }
      }
      alert(errorMessage)
    }
  })
  .catch(error => {
    console.error('Error updating mappings:', error)
    alert('Error updating mappings. Please try again.')
  })
}

const submitRegularForm = () => {
  const payload = {
    component: selectedComponent.value,
    comValue: [],
    status: 1
  }

  formRows.value.forEach(row => {
    // console.log(row);
    payload.comValue.push({
      state: selectedComponent.value === 'PD' ? 0 : row.state,
      name: selectedComponent.value === 'PD' ? row.pdComponent : row.slsId,
      // slsPD: selectedComponent.value === 'PD' ? null : row.slsPD, // Send slsPD only for SL type
      slsPD: row.slsPD, // Send slsPD only for SL type
      slsCode: selectedComponent.value === 'PD' ? null : row.slsId, // Send slsCode for SL type
      slsName: selectedComponent.value === 'PD' ? null : row.slsName, // Send slsName for SL type
      fullSlsName: selectedComponent.value === 'PD' ? null : (row.slsId && row.slsName ? `${row.slsId} - ${row.slsName}` : null) // Send fullSlsName for SL type
    })
  })

  router.post(route('pd-sls.store'), payload, {
    onSuccess: () => {
      selectedComponent.value = ''
      formRows.value = [
        { state: '', pdComponent: '', slsId: '', slsPD: '', slsName: '', slsList: '' }
      ]
      fetchSavedData()
      fetchPDComponentsData() // Refresh PD components list as well
      alert('Saved successfully!')
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed')
    }
  })
}

// Watch for changes in slsPreviewData to reset pagination
watch(slsPreviewData, (newData) => {
  if (newData.length === 0) {
    currentPage.value = 1
  } else if (currentPage.value > Math.ceil(newData.length / itemsPerPage.value)) {
    currentPage.value = Math.ceil(newData.length / itemsPerPage.value)
  }
})

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


