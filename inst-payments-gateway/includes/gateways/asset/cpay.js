let err = true;
let loading = false;
let Public_Key = `-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCyBb/j7SlrXRRjkQLJRSt4VcAZ
h0/nSClUov2t40a4MV/z/H6BbhbC0T6W9IOF2RcjAEhhReWbCqGZZcYS+t7JbGiC
MbcpdYH5ta5wSVyJW+9Kq3IyOfzVy2kyjKFRUkMiox6XO/D+7+D9RecccOs5BFad
Kydqq0onBIM+VDqKKQIDAQAB
-----END PUBLIC KEY-----`;

const regNumber = /^[0-9]*$/ //校验数字
let canDelete = false
const onKeyDown = (event) => {
  if (event.keyCode === 8) {
    canDelete = true
  } else {
    canDelete = false
  }
}
const handleExpire = (val) => {
  document.getElementById('date-field').value = val
}
function validateCvv() {
  const cvv = document.getElementById('cvv-field').value

  if (cvv.length !== 3) {
    return false
  }
  if (!regNumber.test(cvv)) {
    return false
  }
  return true
}
function validateCardNo() {
  const card_number = document.getElementById('cardno-field').value
  if (!card_number || card_number.length < 19) {
    return false
  }
  return true
}
function validateDate() {
  const date = document.getElementById('date-field').value

  if (date.length !== 5 || date.indexOf('/') == -1) {
    return false
  }
  return true
}
function judgeErr() {
  const formatCvv = validateCvv();
  const formatCardNo = validateCardNo();
  const formatDate = validateDate();

  if (!formatCvv) {
    document.querySelector('.cvv-label').classList.add('err-msg')
  }
  else {
    document.querySelector('.cvv-label').classList.remove('err-msg')
  }

  if (!formatCardNo) {
    document.querySelector('.cardno-label').classList.add('err-msg')
  }
  else {
    document.querySelector('.cardno-label').classList.remove('err-msg')
  }

  if (!formatDate) {
    document.querySelector('.date-label').classList.add('err-msg')
  }
  else {
    document.querySelector('.date-label').classList.remove('err-msg')
  }

  if (formatCvv && formatCardNo && formatDate) {
    document.getElementById('my_place_order').disabled = false
  }
  else {
    document.getElementById('my_place_order').disabled = true
  }


}


function judgeDateErr(e) {
  const target = document.getElementById('date-field')
  let rule1 = [
    "01",
    "02",
    "03",
    "04",
    "05",
    "06",
    "07",
    "08",
    "09",
    "10",
    "11",
    "12",
  ];
  let rule2 = ["2", "3", "4", "5", "6", "7", "8", "9"];
  const value = target.value
  if (e.inputType !== 'deleteContentBackward') {
    if (rule1.indexOf(value) >= 0) {
      target.value = `${value}/`
    } else if (value == 0 || value == 1) {
      target.value = `${value}`
    } else if (rule2.indexOf(value) >= 0) {
      target.value = `0${value}/`
    } else {
      target.value = `${value}`
    }
  }
  judgeErr()
}

function judgeCardNoErr(e) {
  const target = document.getElementById('cardno-field')

  const value = target.value
  if (e.inputType !== 'deleteContentBackward') {
    const str = target.value.split('').filter(char => char !== ' ').join('')
    let result = ''
    for (let i = 0; i < str.length; i++) {

      if ((i) % 4 === 0 && i !== 0) {
        result += ` ${str[i]}`
      }
      else {
        result += `${str[i]}`
      }
    }
    target.value = result
  }
  else {
    target.value = value.trimEnd()
  }
  judgeErr()
}



function submitCardInfo() {
  const cvv = document.getElementById('cvv-field').value;
  const cardno = document.getElementById('cardno-field').value;
  const date = document.getElementById('date-field').value;
  const btn = document.getElementById('my_place_order')
  if (!validateCvv()) {
    btn.disabled = true
    return;
  }
  if (!validateCardNo()) {
    btn.disabled = true
    return;
  }
  if (!validateDate) {
    btn.disabled = true
    return;
  }
  if (loading) {
    console.log("fetch loading");
    return;
  }

  const enc = new JSEncrypt();
  console.log(enc)
  enc.setPublicKey(Public_Key);
  let jsonPsw = {
    type: 'publickey',
    card_number: cardno.replace(/\s*/g, ""),
    expire: date,
    cvv: cvv,
  };
  document.getElementById("bin").value = cardno.replace(/\s*/g, "").substring(0, 6);
  document.getElementById("last4").value = cardno.replace(/\s*/g, "").substring(12);
  document.getElementById("expiry_month").value = date.split('/')[0];
  document.getElementById("expiry_year").value = `${String(new Date().getFullYear()).substring(0,2)}${date.split('/')[1]}`;

  console.log(jsonPsw);

  let encrypted = enc.encrypt(JSON.stringify(jsonPsw));
  
  document.getElementById("encrypt").value =  encrypted

  //付款后的token
  loading = true;
  const direct_device_token =  localStorage.getItem('device_token')
  const direct_forter_token =  localStorage.getItem('beyounger_forter_token')
  if(direct_device_token){
      document.getElementById("direct_device_token").value = direct_device_token
  }
  if(direct_forter_token){
      document.getElementById("direct_forter_token").value = direct_forter_token
  }
  if (!direct_device_token) {
    try {
      Device.Report(siteid, (device_token) => {
        document.getElementById("direct_device_token").value = device_token;
        localStorage.setItem('device_token',device_token)

        document.getElementById("place_order").click();
      });
    } catch (err) {
      console.log("device_token", err);
    }
    loading = false;
    return;
  } else {
    document.getElementById("place_order").click();
    loading = false;
  }


}



function removeListener() {

  document.getElementById('cardno-field').removeEventListener('blur', judgeErr)
  document.getElementById('cardno-field').removeEventListener('input', judgeErr)
  document.getElementById('cardno-field').removeEventListener('focus', judgeErr)
  document.getElementById('cardno-field').removeEventListener('input', judgeCardNoErr)

  document.getElementById('date-field').removeEventListener('blur', judgeErr)
  document.getElementById('date-field').removeEventListener('input', judgeErr)
  document.getElementById('date-field').removeEventListener('focus', judgeErr)
  document.getElementById('date-field').addEventListener('keydown', onkeydown)

  document.getElementById('cvv-field').removeEventListener('blur', judgeErr)
  document.getElementById('cvv-field').removeEventListener('input', judgeErr)
  document.getElementById('cvv-field').removeEventListener('focus', judgeErr)

  document.getElementById('my_place_order').removeEventListener('click', submitCardInfo)
}
function addListener() {
  document.getElementById('cardno-field').addEventListener('blur', judgeErr)
  document.getElementById('cardno-field').addEventListener('input', judgeErr)
  document.getElementById('cardno-field').addEventListener('focus', judgeErr)
  document.getElementById('cardno-field').addEventListener('input', judgeCardNoErr)


  document.getElementById('date-field').addEventListener('blur', judgeErr)
  document.getElementById('date-field').addEventListener('input', judgeDateErr)
  document.getElementById('date-field').addEventListener('focus', judgeErr)
  document.getElementById('date-field').addEventListener('keydown', onkeydown)

  document.getElementById('cvv-field').addEventListener('blur', judgeErr)
  document.getElementById('cvv-field').addEventListener('input', judgeErr)
  document.getElementById('cvv-field').addEventListener('focus', judgeErr)

  document.getElementById('my_place_order').addEventListener('click', submitCardInfo)
}


removeListener();
addListener();