<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Mother Sanction Module</div>

                </div>

                <div class="card-body">
                  <div class="row">
                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select class="form-select" v-model="financialYear">
                          <option disabled value="">Select Financial Year</option>
                          <option value="2024-2025">2024–2025</option>
                        </select>
                      </div>
                    </div>

                    <!-- State -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="stateSelect">State</label>
                       <select v-model="selectedState" @change="fetchSlsData" class="form-select" id="stateSelect">
                          <option value="">--- Select State ---</option>
                          <option v-for="state in states" :key="state.id" :value="state.id">
                            {{ state.name }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- MS Sequence No. -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="msSequence">MS Sequence No.</label>
                        <select class="form-select" v-model="msSequenceNo" id="msSequence">
                          <option value="">Select</option>
                          <option value="1">1</option>
                          <option value="2">2</option>
                        </select>
                      </div>
                    </div>

                    <!-- Sanction/File No -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="sanctionNo">Sanction No./File No.</label>
                        <input type="text" class="form-control" id="sanctionNo" v-model="sanctionNo" placeholder="67890">
                      </div>
                    </div>

                     <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                          <label for="ifdNo">IFD No.</label>
                          <input type="text" class="form-control" id="ifdNo" v-model="ifdNo">
                        </div>
                      </div>

                    <!-- Sanction Date -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="sanctionDate">Sanction Date</label>
                        <input type="date" class="form-control" id="sanctionDate" v-model="sanctionDate">
                      </div>
                    </div>

                    <!-- KY MS No -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="kyMsNo">KY MS No.</label>
                        <input type="text" class="form-control" id="kyMsNo" :value="kyMsNo" disabled>


                      </div>
                    </div>

                    <!-- SLS ID -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="slsId">SLS ID</label>
                        <select class="form-control" v-model="selectedSlsId" @change="fetchFundAllocationData">
                          <option value="">--- Select SLS ID ---</option>
                          <option v-for="sls in slsData" :key="sls.id" :value="sls.name">{{ sls.name }}</option>
                        </select>
                      </div>
                    </div>

                    <!-- PD/Component -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="pdComponent">PD/Component</label>
                        <input
                            type="text"
                            class="form-control"
                            id="pdComponent"
                            v-model="pdComponent"
                            disabled 
                          >
                      </div>
                    </div>

                   

                    <!-- Total Sanction Amount -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="totalSanction">Total Mother Sanction Amount(current)</label>
                        <input type="text" class="form-control" id="totalSanction" :value="totalSanctionAmount.toLocaleString()" disabled>

                      </div>
                    </div>
                  </div>

                  <!-- Budget Table -->
                  <div class="table-responsive mt-3">
                    <table class="table table-bordered table-head-bg-primary">
                      <tbody>
                        <tr>
                          <td width="20%">
                            <label class="highlight_textbox">Budget Head</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Category</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Available Fund Amount</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Mother Sanction Amount</label>
                          </td>
                        </tr>

                        <!-- Example Rows -->
                        <tr v-for="(row, index) in reappropriations" :key="index">
                          <td>
                            <select v-model="row.budget_head" class="form-select" @change="fetchBudgetDetails(row)">
                              <option value="">--- Budget Head ---</option>
                              <option 
                                v-for="(item, idx) in fundAllocations.filter(f => !selectedBudgetHeads.includes(f.budget) || f.budget === row.budget_head)"
                                :key="idx" 
                                :value="item.budget"
                              >
                                {{ item.budget }}
                              </option>
                            </select>
                          </td>
                          <td>
                            <input type="text" v-model="row.category" class="form-control tableform-control-withoutbg" disabled>
                          </td>
                          <td>
                            <input type="text" v-model="row.available_amount" class="form-control tableform-control-withoutbg" disabled>
                          </td>
                          <td>
                           <input 
                              type="number" 
                              v-model="row.sanction_amount" 
                              class="form-control tableform-control-withoutbg"
                              @input="checkSanctionAmount(row)"
                            >
                          </td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-danger" @click="removeReappropriationRow(index)" v-if="reappropriations.length > 1">×</button>
                          </td>
                        </tr>
                       
                      </tbody>
                    </table>
                    <button class="btn btn-primary me-1 mb-4" @click="addReappropriationRow">
                      + Add More
                    </button>

                  </div>

                  <!-- File Uploads -->
                  <div class="bg-body-secondary mt-4 mb-4 rebdr">
                    <div class="row">
                      <div class="col-md-6 col-lg-6">
                        <div class="form-group">
                          <label for="ucFile">UC Received From State</label>
                          <input type="file" class="form-control" id="ucFile"
                          @change="handleUcFileChange" name="uc_file_path" />
                        </div>
                          <!-- UC File Preview -->
                      <div v-if="ucFilePreview" class="mt-2">
                        <template v-if="ucFile?.type.startsWith('image/')">
                          <img :src="ucFilePreview" alt="UC File Preview" style="max-width: 200px;" />
                        </template>
                        <template v-else>
                          <a :href="ucFilePreview" target="_blank" class="btn btn-outline-primary btn-sm">Preview UC File</a>
                        </template>
                      </div>
                      </div>
                    
                      <div class="col-md-6 col-lg-6">
                        <div class="form-group">
                          <label for="sanctionFile">Signed Copy of Mother Sanction</label>
                          <input type="file" class="form-control" id="sanctionFile"
                          @change="handleSanctionFileChange" name="signed_copy_path" />
                        </div>
                        <div v-if="sanctionFilePreview" class="mt-2">
                        <template v-if="sanctionFile?.type.startsWith('image/')">
                          <img :src="sanctionFilePreview" alt="Sanction File Preview" style="max-width: 200px;" />
                        </template>
                        <template v-else>
                          <a :href="sanctionFilePreview" target="_blank" class="btn btn-outline-primary btn-sm">Preview Sanction File</a>
                        </template>
                      </div>
                      </div>
                      
                    </div>
                  </div>
                </div>

                <!-- Footer Buttons -->
                <div class="card-footer">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button class="btn btn-primary me-1" @click="submitData(0)">Save as Draft</button>
                      <button class="btn btn-success me-1" @click="submitData(1)">Submit</button>
                      <button class="btn btn-danger me-1" @click="resetForm">Reset</button>

                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed , watch} from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { router } from '@inertiajs/vue3' 


const states = ref([])
const selectedState = ref('')
const budgetHeads = ref([])
const selectedSlsId = ref('');
const slsData = ref([]);
const fundAllocations = ref([]);
const financialYear = ref('');
const msSequenceNo = ref('');
const sanctionNo = ref('');
const ifdNo = ref('');
const sanctionDate = ref('');
//const kyMsNo = ref('');
const pdComponent = ref('');
const ucFile = ref(null);
const sanctionFile = ref(null);
const ucFilePreview = ref(null)
const sanctionFilePreview = ref(null)
const stateCodeMap = {
  1: 'UP',
  2: 'MH',
  3: 'RJ',
  4: 'OR',
  // Replace with your actual state.id and 2-letter codes
};


const kyMsNo = computed(() => {
  if (!financialYear.value || !selectedState.value || !msSequenceNo.value || !selectedSlsId.value) {
    return '';
  }

  const yearPart = financialYear.value.split('-')[0].slice(-2); // "2024-2025" => "24"
  const stateCode = selectedState.value || 'XX';   // e.g., 'UP'
  const sequenceNo = msSequenceNo.value.toString().padStart(2, '0'); // 1 => 01
  const sls = selectedSlsId.value;

  return `MS${yearPart}${stateCode}${sequenceNo}${sls}`;
});


const resetForm = () => {
  financialYear.value = '';
  selectedState.value = '';
  msSequenceNo.value = '';
  sanctionNo.value = '';
  ifdNo.value = '';
  sanctionDate.value = '';
  //kyMsNo.value = '';
  selectedSlsId.value = '';
  pdComponent.value = '';

  if (ucFilePreview.value) URL.revokeObjectURL(ucFilePreview.value);
  if (sanctionFilePreview.value) URL.revokeObjectURL(sanctionFilePreview.value);

  ucFile.value = null;
  sanctionFile.value = null;
  ucFilePreview.value = null;
  sanctionFilePreview.value = null;

  reappropriations.value = [{ budget_head: '', category: '', available_amount: '', sanction_amount: '' }];
};







const reappropriations = ref([
  { budget_head: '', category: '', available_amount: '', sanction_amount: '' }
])

const handleUcFileChange = (e) => {
  const file = e.target.files[0];
  ucFile.value = file;
  if (file) {
    ucFilePreview.value = URL.createObjectURL(file);
  }
};



const checkSanctionAmount = (row) => {
  const sanction = parseFloat(row.sanction_amount);
  const available = parseFloat(row.available_amount);

  if (!isNaN(sanction) && !isNaN(available) && sanction > available) {
    alert("    Sanction amount exceeds available funds!");
    row.sanction_amount = ''; // Optionally reset the value
  }
};

const selectedBudgetHeads = computed(() =>
  reappropriations.value.map(row => row.budget_head).filter(Boolean)
);




const handleSanctionFileChange = (e) => {
  const file = e.target.files[0];
  sanctionFile.value = file;
  if (file) {
    sanctionFilePreview.value = URL.createObjectURL(file);
  }
};


function removeReappropriationRow(index) {
  reappropriations.value.splice(index, 1);
}


const submitData = async (status) => {
  const formData = new FormData();
  alert(kyMsNo.value);
  formData.append('financial_year', financialYear.value);
  formData.append('state_id', selectedState.value);
  formData.append('ms_sequence_no', msSequenceNo.value);
  formData.append('file_no', sanctionNo.value);
  formData.append('ifd_no', ifdNo.value);
  formData.append('sanction_date', sanctionDate.value);
  formData.append('ky_ms_no', kyMsNo.value);

  formData.append('sls_name', selectedSlsId.value);
  formData.append('pd_component', pdComponent.value);
  formData.append('total_mother_sanction_amount', totalSanctionAmount.value);
  formData.append('status', status);
  console.log("formDataformDataformData",formData);
  // Files
  if (ucFile.value) formData.append('uc_file_path', ucFile.value);
  if (sanctionFile.value) formData.append('signed_copy_path', sanctionFile.value);

  formData.append('reappropriations', JSON.stringify(reappropriations.value));


 router.post(route('addMotherSanction'), formData, {
    onSuccess: () => {
      alert(status === 1 ? 'Submitted successfully!' : 'Saved as draft!')
      resetForm()
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed. Check input and try again.')
    }
  })
};



function addReappropriationRow() {
  reappropriations.value.push({
    budget_head: '',
    category: '',
    available_amount: '',
    sanction_amount: ''
  })
}

onMounted(async () => {
  try {
    const [statesRes, budgetHeadsRes] = await Promise.all([
      fetch('/api/states'),
      fetch('/api/budget-heads')
    ]);

    if (statesRes.ok) {
      states.value = await statesRes.json();
    }

    if (budgetHeadsRes.ok) {
      budgetHeads.value = await budgetHeadsRes.json();
    }

  } catch (error) {
    console.error('Fetch error:', error)
  }
})

async function fetchSlsData() {
  if (!selectedState.value) {
    slsData.value = [];
    return;
  }

  try {
    const res = await fetch(`/api/sls-data/${selectedState.value}`);
    if (res.ok) {
      slsData.value = await res.json();
    } else {
      console.error('Failed to fetch SLS data');
    }
  } catch (error) {
    console.error('Error fetching SLS data:', error);
  }
}




const totalSanctionAmount = computed(() => {
  return reappropriations.value.reduce((sum, row) => {
    const amount = parseFloat(row.sanction_amount);
    return sum + (isNaN(amount) ? 0 : amount);
  }, 0);
});

const fetchFundAllocationData = async () => {
  if (!selectedSlsId.value || !selectedState.value) return;

  try {
    const response = await fetch(`/api/fund-allocation/${selectedSlsId.value}/${selectedState.value}`);
    if (response.ok) {
      const data = await response.json();
      fundAllocations.value = data;

      // ✅ Set PD/Component from the first item
      if (data.length > 0) {
        pdComponent.value = data[0].slsPD;
      } else {
        pdComponent.value = '';
      }
    } else {
      console.error('Failed to fetch fund allocation data');
      fundAllocations.value = [];
      pdComponent.value = '';
    }
  } catch (error) {
    console.error('Error fetching fund allocation data:', error);
    fundAllocations.value = [];
    pdComponent.value = '';
  }
};


const fetchBudgetDetails = async (row) => {
  if (!row.budget_head || !selectedSlsId.value || !selectedState.value) return;

  try {
    const res = await fetch(`/api/fund-allocation/by-budget?budget=${encodeURIComponent(row.budget_head)}&sls_id=${encodeURIComponent(selectedSlsId.value)}&state_id=${selectedState.value}`);
    if (res.ok) {
      const data = await res.json();
      row.category = data.category;
      row.available_amount = data.amount;
    } else {
      row.category = '';
      row.available_amount = '';
      console.error('Budget details not found');
    }
  } catch (error) {
    console.error('Error fetching budget details:', error);
  }
};





</script>
