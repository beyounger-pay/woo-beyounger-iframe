let loading = false; //订单付款请求中
//var appId = 380; //商户ID 必填
const isToken = false; // token支付 必须是true

console.log('===var_app_id===',plugin_name_ajax_object.var_app_id);
var appId = plugin_name_ajax_object.var_app_id;
if (!appId) {
  appId = '380';
}



var submitData = (postData) =>{
  if (loading) {
    alert("fetch loading");
    return;
  }
  // 如果当前页面有其他的支付信息也可以一并提交到后台

  //付款后的token
  const token = postData.token;

  if (!token) {
    alert("token can not be null");
    return;
  }
  console.log('latest js_var2:',document.getElementById("js_var2").value);
  loading = true;
  const glo_device_token =  localStorage.getItem('device_token')
  const glo_forter_token =  localStorage.getItem('beyounger_forter_token')
  if(glo_device_token){
      document.getElementById("glo_device_token").value = glo_device_token
  }
  if(glo_forter_token){
      document.getElementById("glo_forter_token").value = glo_forter_token
  }
  if (!glo_device_token) {
    try {
      Device.Report(siteid, (device_token) => {
        document.getElementById("glo_device_token").value = device_token;
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

var initDeviceToken = () => {
  const siteid = window.location.origin
  if (typeof Device) {
    Device.Report(siteid, false).then((token) => {
      console.log("d_token", token);
      document.getElementById("glo_device_token").value = token;
      localStorage.setItem('device_token', token)
    });
  } else {
    console.log("fail");
  }
};


