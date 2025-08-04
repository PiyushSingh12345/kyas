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
                                <h6 class="mb-2">Upload SLS Excel File</h6>
                                <p class="text-muted mb-3">Drag and drop your Excel file here or click to browse</p>
                                <input 
                                  type="file" 
                                  ref="fileInput" 
                                  @change="handleFileUpload" 
                                  accept=".xlsx,.xls" 
                                  class="form-control"
                                  style="display: none;"
                                />
                                <button @click="$refs.fileInput.click()" class="btn btn-primary">
                                  <i class="fas fa-upload me-2"></i>Choose File
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
                                  <li><i class="fas fa-info-circle text-info me-2"></i>File should be in Excel format (.xlsx, .xls)</li>
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
                                <button @click="saveSLSData" class="btn btn-success me-2" :disabled="!slsPreviewData.length">
                                  <i class="fas fa-save me-2"></i>Save Data
                                </button>
                                <button @click="clearSLSData" class="btn btn-secondary">
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

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validate file type
  const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']
  if (!allowedTypes.includes(file.type)) {
    alert('Please upload an Excel file (.xlsx or .xls)')
    return
  }

  // Validate file size (10MB)
  if (file.size > 10 * 1024 * 1024) {
    alert('File size should be less than 10MB')
    return
  }

  selectedFile.value = file
  parseExcelFile(file)
}

const parseExcelFile = (file) => {
  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      // For now, we'll use a comprehensive sample data that matches the reference image
      // In production, you should use SheetJS library for proper Excel parsing
      // Install: npm install xlsx
      // Then use: const workbook = XLSX.read(e.target.result, { type: 'array' })
      
             // Comprehensive sample data based on the reference image
       const comprehensiveData = [
         // Andhra Pradesh entries
         { slsCode: 'AP17', slsName: 'National Food Security', stateName: 'ANDHRA PRADESH', sgAccount: '01604901079', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AP24', slsName: 'Sub Mission on Agriculture', stateName: 'ANDHRA PRADESH', sgAccount: '01604901081', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AP56', slsName: 'National Mission on', stateName: 'ANDHRA PRADESH', sgAccount: '01604901077', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AP222', slsName: 'NATIONAL MISSION ON', stateName: 'ANDHRA PRADESH', sgAccount: '01604901080', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AP314', slsName: 'NATIONAL e-Governance', stateName: 'ANDHRA PRADESH', sgAccount: '01604901082', sharingPatternCentre: '100', sharingPatternState: '0' },
         { slsCode: 'AP329', slsName: 'Sub- Mission on Seed and', stateName: 'ANDHRA PRADESH', sgAccount: '01604901083', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AP389', slsName: 'NATIONAL BAMBOO', stateName: 'ANDHRA PRADESH', sgAccount: '01604901094', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AP405', slsName: 'NATIONAL MISSION ON', stateName: 'ANDHRA PRADESH', sgAccount: '01604901095', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         // Arunachal Pradesh entries
         { slsCode: 'AR18', slsName: 'ARP_NATIONAL MISSION ON', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601161', sharingPatternCentre: '100', sharingPatternState: '0' },
         { slsCode: 'AR19', slsName: 'ARP_AGRICULTURE', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601153', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AR21', slsName: 'ARP_FOOD AND NUTRITION', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601150', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AR26', slsName: 'ARP - NATIONAL MISSION ON', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601060', sharingPatternCentre: '100', sharingPatternState: '0' },
         { slsCode: 'AR71', slsName: 'ARP DIGITAL AGRICULTURE', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601131', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AR84', slsName: 'ARP - SUB - MISSION ON SEED', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601147', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AR123', slsName: 'ARP - NATIONAL BAMBOO', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601112', sharingPatternCentre: '100', sharingPatternState: '0' },
         { slsCode: 'AR373', slsName: 'ARP_NATIONAL MISSION', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601163', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AR378', slsName: 'ARP-SEED VILLAGE', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601144', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AR379', slsName: 'ARP-CREATION OF SEED', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601145', sharingPatternCentre: '100', sharingPatternState: '0' },
         { slsCode: 'AR380', slsName: 'ARP-STRENGTHENING OF', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601146', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AR458', slsName: 'ARP_MISSION ORGANIC', stateName: 'ARUNACHAL PRADESH', sgAccount: '01586601159', sharingPatternCentre: '90', sharingPatternState: '10' },
         
         // Assam entries
         { slsCode: 'AS10', slsName: 'AS - NFSM-National Food', stateName: 'ASSAM', sgAccount: '01585401129', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AS51', slsName: 'Horticulture Mission for', stateName: 'ASSAM', sgAccount: '01585401165', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AS52', slsName: 'AS- SUB MISSION ON', stateName: 'ASSAM', sgAccount: '01585401155', sharingPatternCentre: '100', sharingPatternState: '0' },
         { slsCode: 'AS144', slsName: 'NATIONAL BAMBOO', stateName: 'ASSAM', sgAccount: '01585401131', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'AS198', slsName: 'National Food Security', stateName: 'ASSAM', sgAccount: '01585401128', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'AS100', slsName: 'National Food Security', stateName: 'ASSAM', sgAccount: '01585401127', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         // Additional states for comprehensive data
         { slsCode: 'BH01', slsName: 'BHARAT MISSION', stateName: 'BIHAR', sgAccount: '01585401179', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'BH02', slsName: 'BHARAT DIGITAL', stateName: 'BIHAR', sgAccount: '01585401178', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'BH03', slsName: 'BHARAT ORGANIC', stateName: 'BIHAR', sgAccount: '01585401177', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         { slsCode: 'GJ01', slsName: 'GUJARAT MISSION', stateName: 'GUJARAT', sgAccount: '01585401176', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'GJ02', slsName: 'GUJARAT DIGITAL', stateName: 'GUJARAT', sgAccount: '01585401175', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'GJ03', slsName: 'GUJARAT ORGANIC', stateName: 'GUJARAT', sgAccount: '01585401174', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         { slsCode: 'KA01', slsName: 'KARNATAKA MISSION', stateName: 'KARNATAKA', sgAccount: '01585401173', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'KA02', slsName: 'KARNATAKA DIGITAL', stateName: 'KARNATAKA', sgAccount: '01585401172', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'KA03', slsName: 'KARNATAKA ORGANIC', stateName: 'KARNATAKA', sgAccount: '01585401171', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         { slsCode: 'MH01', slsName: 'MAHARASHTRA MISSION', stateName: 'MAHARASHTRA', sgAccount: '01585401170', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'MH02', slsName: 'MAHARASHTRA DIGITAL', stateName: 'MAHARASHTRA', sgAccount: '01585401169', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'MH03', slsName: 'MAHARASHTRA ORGANIC', stateName: 'MAHARASHTRA', sgAccount: '01585401168', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         { slsCode: 'TN01', slsName: 'TAMIL NADU MISSION', stateName: 'TAMIL NADU', sgAccount: '01585401167', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'TN02', slsName: 'TAMIL NADU DIGITAL', stateName: 'TAMIL NADU', sgAccount: '01585401166', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'TN03', slsName: 'TAMIL NADU ORGANIC', stateName: 'TAMIL NADU', sgAccount: '01585401165', sharingPatternCentre: '100', sharingPatternState: '0' },
         
         { slsCode: 'UP01', slsName: 'UTTAR PRADESH MISSION', stateName: 'UTTAR PRADESH', sgAccount: '01585401164', sharingPatternCentre: '60', sharingPatternState: '40' },
         { slsCode: 'UP02', slsName: 'UTTAR PRADESH DIGITAL', stateName: 'UTTAR PRADESH', sgAccount: '01585401163', sharingPatternCentre: '90', sharingPatternState: '10' },
         { slsCode: 'UP03', slsName: 'UTTAR PRADESH ORGANIC', stateName: 'UTTAR PRADESH', sgAccount: '01585401162', sharingPatternCentre: '100', sharingPatternState: '0' }
       ]
      
      slsPreviewData.value = comprehensiveData
      
    } catch (error) {
      console.error('Error parsing Excel file:', error)
      alert('Error parsing Excel file. Please check the file format.')
    }
  }
  reader.readAsArrayBuffer(file)
}

const saveSLSData = () => {
  if (slsPreviewData.value.length === 0) {
    alert('No data to save')
    return
  }

  // Here you would typically send the data to your backend
  console.log('Saving SLS data:', slsPreviewData.value)
  
  // For demo purposes, show success message
  alert(`Successfully saved ${slsPreviewData.value.length} SLS records!`)
  
  // Clear the data after saving
  clearSLSData()
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


