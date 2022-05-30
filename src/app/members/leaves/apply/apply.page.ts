import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { formatDate } from '@angular/common';
import { LeavesProvider } from 'src/app/services/leaves/leaves';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { LoadingController, NavController } from '@ionic/angular';

@Component({
  selector: 'app-apply',
  templateUrl: './apply.page.html',
  styleUrls: ['./apply.page.scss'],
})
export class ApplyPage implements OnInit {
    myParam: any;
    leaveTypeId: any;
    leaveName: any = '';
    dateFrom: any;
    dateTo: any;
    leaveRequest: any;
    isPartial: any;
    startTime: any;
    endTime: any;
    reason: any;
    startMsg: any;
    endMsg: any;
    ifNs: any;
    lunchRequierd: any = false;
    dis_endTime: any;
    dis_startTime: any;
    canApply: boolean = false;
    isLockPayroll: boolean = false;
    currentYear: any;
    whatHalf: any;
    isHalf: any;
    finalRecal: boolean = false;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        public leaveProv: LeavesProvider,
        public comCtrl: MyCommonServices,
        public loadingCtrl: LoadingController,
        public nav: NavController,
    ) { 
        this.route.queryParams.subscribe(params => {
            if (this.router.getCurrentNavigation().extras.state) {
                this.myParam = this.router.getCurrentNavigation().extras.state.details;
                console.log(this.myParam);
            }
        });
        this.isPartial = false;
        this.whatHalf = "first_half";
    }

    ngOnInit() {
        var new_date = new Date();
        this.currentYear = new_date.getFullYear();
        
        this.leaveName = this.myParam.leave_type;
        this.leaveTypeId = this.myParam.leave_type_id;
        this.leaveRequest = "0.00";
        console.log(this.leaveTypeId);
    }

    gotoBack() {
        this.router.navigate(['/members/leaves']);
    }

    async presentLoading() {
        const loading = await this.loadingCtrl.create({
          message: 'Please wait...',
          duration: 10000
        });
        await loading.present();
    
        const { role, data } = await loading.onDidDismiss();
    
        console.log('Loading dismissed!');
    }

    recalc() {
        this.finalRecal = false;
    }

    applyLeave() {
        if (this.leaveRequest <= 0 || this.leaveRequest == 'Calculate total leave') {
            this.comCtrl.presentToast('Calculate leave requested before submitting.', '');
            return false;
        }
        
        this.presentLoading();

        this.leaveProv.submitLeave(this.dateFrom, this.startTime, this.dateTo, this.endTime, this.isPartial,
            this.leaveTypeId, this.lunchRequierd, this.leaveRequest, "", this.reason, "", 0).then(res=>{
            console.log(res);
            console.log("haiz");
            this.loadingCtrl.dismiss();
            if (res) {
                let rslt: any = res;
                if (rslt.error) {
                    if (rslt.existing_leave) {
                        let msg: any = rslt.err_msg;
                        msg += "<br><br>Leave Type: "+rslt.leave_type;
                        msg += "<br>Date Filed: "+rslt.date_filed;
                        msg += "<br>Date Start: "+rslt.date_start;
                        msg += "<br>Date End: "+rslt.date_end;
                        msg += "<br>Status: "+rslt.leave_application_status;

                        this.comCtrl.presentOnebuttonAlert("Existing Leave", msg);
                    } else {
                        let msg: any = rslt.err_msg;
                        this.comCtrl.presentOnebuttonAlert("Opps!", msg);
                    }
                } else {
                    this.comCtrl.presentToast('Your leave request has been submitted.', 'success');                    
                    this.nav.navigateBack('/members/leaves');
                }
            } else {
                this.comCtrl.presentToast("hmmn, i can't process your request now you may try again at a different time", 'error');
            }
            
        }).catch(err=>{
            console.log(err);
        });
    }

    recalcReq() {
        this.calculateLeaveRequest(true);
    }

    onChangeDate() {    
        this.leaveRequest = "Calculate total leave";
        console.log("aaaaaaa");
        console.log(this.dateFrom); 
        let aw = new Date(this.dateFrom);
        
        let cd = formatDate(aw, 'MM/dd/yyyy', 'en-US');
        this.dateFrom = formatDate(aw, 'MM/dd/yyyy', 'en-US');
        if (!this.dateTo) {
            this.dateTo = cd;
        }
        console.log(cd);        
        this.checkWorkSched();
        // this.checkPayrollLock();
        this.finalRecal = false;
    }

    onChangeDateTo() { 
        this.leaveRequest = "Calculate total leave";       
        let aw = new Date(this.dateTo);
        let cd = formatDate(aw, 'yyyy-MM-dd', 'en-US');
        if (!this.dateFrom) {
            this.dateFrom = cd;
            this.checkWorkSched();
        }
        // this.dateTo = cd;
        // this.calculateLeaveRequest();
        this.finalRecal = false;
    }

    onChangeTime() {
        this.leaveRequest = "Calculate total leave";        
        // this.calculateLeaveRequest();
        this.finalRecal = false;
    }

    calculateLeaveRequest(isAction?: boolean) {
        var req_lunch = (this.lunchRequierd) ? "1" : "0";
        let aw = new Date(this.startTime);
        let aws = new Date(this.endTime);
        var s_time = formatDate(aw, 'hh:mm a', 'en-US');
        var e_time = formatDate(aws, 'hh:mm a', 'en-US');
        this.leaveProv.getTotalLeaveRequest(this.dateFrom, s_time, this.dateTo, e_time, "", this.ifNs, this.isPartial, this.leaveTypeId, req_lunch).then(res=>{
            console.log(res);
            this.leaveRequest = res;
            if (isAction) {
                this.finalRecal = true;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    checkWorkSched() {
        // this.leaveProv.checkWorkSchedule(this.dateFrom, this.dateTo, this.isPartial, this.leaveTypeId, "", this.isHalf, this.whatHalf).then(res=>{            
        this.leaveProv.checkWorkSchedule(this.dateFrom, this.dateTo, this.isPartial, this.leaveTypeId, "").then(res=>{            
            if (res) {
                let rslt: any = res;
                if (rslt.error) {
                    this.endMsg = rslt.eyour_shift;
                    this.startMsg = rslt.your_shift;

                    let aw = new Date(rslt.estart_time);
                    let aws = new Date(rslt.eend_time);

                    this.startTime = aw;
                    
                    this.dis_startTime = aw.toISOString();
                    this.endTime = aws;
                    this.dis_endTime = aws.toISOString();
                    
                    this.ifNs = rslt.if_NS;

                    // this.calculateLeaveRequest();
                }
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    checkPayrollLock() {

    }

    korewa_suji_desu_ka(str: any) {        
        var parsed = parseFloat(str);
        var casted = +str;
        return parsed === casted  && !isNaN(parsed) && !isNaN(casted);
    }

}
