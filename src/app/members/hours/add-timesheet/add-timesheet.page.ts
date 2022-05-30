import { Component, OnInit } from '@angular/core';
import { HoursProvider } from 'src/app/services/hours/hours';
import { AlertController, NavController, LoadingController, ToastController } from '@ionic/angular';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { formatDate } from '@angular/common';

@Component({
  selector: 'app-add-timesheet',
  templateUrl: './add-timesheet.page.html',
  styleUrls: ['./add-timesheet.page.scss'],
})
export class AddTimesheetPage implements OnInit {
  currentDate: any;
  reason: any = '';
  logDetails: any = "";
  isHalfDay: boolean = false;
  timeIn: any;
  lunchOut: any;
  lunchIn: any;
  firstBreakIn: any;
  firstBreakOut: any;
  secondBreakOut: any;
  secondBreakIn: any;
  timeOut: any;
  totalHrs: any;
  empTimeinId: any;
  checkBreak: any;
  hasBreakOne: boolean = false;
  hasBreakTwo: boolean = false;
  hasLunch: boolean = false;
  isPristine: boolean = false;
  halfDay: boolean = false;
  isSplit: boolean = false;
  splitId: any = '';
  minDate: any;
  maxDate: any;
  isAssumed: any = false;
  islocked: boolean = false;
  toast: any;

  constructor(
    private hoursProv: HoursProvider,
    public alertCtrl: AlertController,
    public comCtrl: MyCommonServices,
    public nav: NavController,
    public loadingCtrl: LoadingController,
    public toastCtrl: ToastController,
  ) { 
    this.hasBreakOne = false;
    this.hasBreakTwo = false;
  }

  ngOnInit() {
    this.checkLock();
  }

  async showToast(msg: any) {
    this.toast = await this.toastCtrl.create({
      message: msg,
      duration: 5000,
      position: 'top',
      cssClass: "error"
    });
    
    this.toast.present();
  }

  onSelectDate(touched?: boolean) {
        
    let logs: any = this.getLogs();
    
    if (logs) {
        if (!logs.timeOut) {
            return false;
        }
        if (!logs.schedDate) {
            return false;
        }
    } else {
        return false;
    }

    this.isPristine = true;
    this.hoursProv.checkTotalHours(logs).then(res=>{
        
        if (res) {
            let rslt: any = res;
            if (!rslt.error) {
                this.totalHrs = rslt.total_hours;
            }

            if (rslt.ecreason) {
                this.showToast(rslt.ecreason);
                
                if (rslt.etime_out_date) {
                  this.toast.ionToastDidDismiss(()=>{
                    this.comCtrl.presentToast(rslt.etime_out_date, "error", 6000);
                  });
                }
            } else {
                if (rslt.etime_out_date) {
                    this.comCtrl.presentToast(rslt.etime_out_date, "error", 6000);
                }
            }
        }
        this.isPristine = false;
    }).catch(err=>{
        console.log(err);
    });
    
}

  onSubmit() {
    if (this.islocked) {
      this.lockAlert();
    }

    let asd = this.getLogs();

    let errs = this.hoursProv.logsValidation(asd);
    if (errs) {
        this.comCtrl.presentLogsError(errs);
        return false;
    }

    this.comCtrl.presentLoadingDefault();

    this.hoursProv.addLogs(asd, this.splitId).then(res=>{
        let rslt: any = res;
        if (res) {
            if (rslt.error) {
                this.comCtrl.presentToast(rslt.error_msg, "error");
            } else {
                this.comCtrl.presentToast('Your request to add time logs has been submitted.', 'success');
                this.nav.navigateBack('/members/timesheets');
            }
            this.loadingCtrl.dismiss();
        }
    }).catch(err=>{
        console.log(err);
    });
  }

  getLogs() {

    var schedDate = (!this.currentDate) ? '' : new Date(this.currentDate);
    var timeInDate = (!this.timeIn) ? '' :  new Date(this.timeIn);
    var timeIn = (!this.timeIn) ? '' :  new Date(this.timeIn);
    var timeOut = (!this.timeOut) ? '' :  new Date(this.timeOut);
    var lunchIn = (!this.lunchIn) ? '' :  new Date(this.lunchIn);
    var lunchOut = (!this.lunchOut) ? '' :  new Date(this.lunchOut);
    var firstBreakIn = (!this.firstBreakIn) ? '' :  new Date(this.firstBreakIn);
    var firstBreakOut = (!this.firstBreakOut) ? '' :  new Date(this.firstBreakOut);
    var secondBreakIn = (!this.secondBreakIn) ? '' :  new Date(this.secondBreakIn);
    var secondBreakOut = (!this.secondBreakOut) ? '' :  new Date(this.secondBreakOut);

    let asd = {
      'schedDate' : (!this.currentDate) ? '' : formatDate(schedDate, 'yyyy-MM-dd', 'en-US'),
      'timeInDate' : (!this.timeIn) ? '' : formatDate(timeInDate, 'yyyy-MM-dd', 'en-US'),
      'timeIn': (!this.timeIn) ? '' : formatDate(timeIn, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'timeOut': (!this.timeOut) ? '' : formatDate(timeOut, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'lunchIn': (!this.lunchIn) ? '' : formatDate(lunchIn, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'lunchOut': (!this.lunchOut) ? '' : formatDate(lunchOut, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'firstBreakIn': (!this.firstBreakIn) ? '' : formatDate(firstBreakIn, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'firstBreakOut': (!this.firstBreakOut) ? '' : formatDate(firstBreakOut, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'secondBreakIn': (!this.secondBreakIn) ? '' : formatDate(secondBreakIn, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'secondBreakOut': (!this.secondBreakOut) ? '' : formatDate(secondBreakOut, 'yyyy-MM-dd HH:mm:ss', 'en-US'),
      'halfday': this.halfDay,
      'reason': this.reason
    };
    
    return asd;
  }

  onChangeDate() {
        
    let now = new Date(this.currentDate);
    let cd = formatDate(now, 'yyyy-MM-dd', 'en-US');
    var plusday = now.setDate(now.getDate() + 1);
    var minusday = now.setDate(now.getDate() - 1);
    this.maxDate = formatDate(plusday, 'yyyy-MM-dd', 'en-US');
    this.minDate = formatDate(minusday, 'yyyy-MM-dd', 'en-US');
    
    this.hoursProv.breakChecker(cd).then(res=>{
        
        if (res) {
            let rslt: any = res;
            if (!rslt.error) {
                this.hasBreakOne = (rslt.break_1) ? true : false;
                this.hasBreakTwo  = (rslt.break_2) ? true : false;
                this.hasLunch  = (rslt.break_in_min) ? true : false;
                this.isAssumed = rslt.assumed;
                if (this.isAssumed) {
                    var lunchout_str = rslt.lunch_out_date + " " + rslt.lunch_out_time;
                    var lo_date = new Date(lunchout_str);
                    // var lunchout_obj = moment(lunchout_str, 'MM-DD-YYYY HH:mm A').parseZone();

                    var lunchin_str = rslt.lunch_in_date + " " + rslt.lunch_in_time;
                    var li_date = new Date(lunchin_str);
                    // var lunchin_obj = moment(lunchin_str, 'MM-DD-YYYY HH:mm A').parseZone();
                    
                    this.lunchOut = lo_date.toISOString();//rslt.assumed;
                    this.lunchIn = li_date.toISOString();
                }
            }
        }            
    }).catch(err=>{
        console.log(err);
    });
    this.checkSplit(cd);
  }

  checkSplit(cd) {
    this.hoursProv.splitChecker(cd).then(res=>{
        if (res) {
            let rslt : any = res;
            if (rslt.is_split) {
                this.isSplit = true;
                this.splitAlert();
            }

        }
    }).catch(err=>{

    });
  }

  async splitAlert() {
    const alert = await this.alertCtrl.create({
      header: 'Opps',
      message: "Sorry app does not support split schedules yet. Please use the desktop browser instead.",
      buttons: [
        {
            text: 'Got it!',
            role: 'cancel',
            handler: () => {
              this.nav.navigateBack('/members/timesheets');
            }
        }
      ]
    });

    await alert.present();
  }

  async lockAlert() {
    const alert = await this.alertCtrl.create({
      header: 'Locked',
      message: "Filing of attendance adjustment is currently suspended by your admin.  You may try again at a different time.",
      buttons: [
        {
            text: 'Got it!',
            role: 'cancel',
            handler: () => {
              this.nav.navigateBack('/members/timesheets');
            }
        }
      ]
    });

    await alert.present();
  }

  checkLock() {
    this.comCtrl.presentLoadingDefault();
    this.hoursProv.checkLocks().then(res=>{
        
        if (res) {
            let rslt: any = res;
            console.log(rslt.error);
            if (!rslt.error) {
                if (rslt.lock_this_timesheet || rslt.recal_timesheet || rslt.recal_payroll) {
                    this.islocked = true;

                    this.lockAlert();
                }
            }
        }
        this.loadingCtrl.dismiss();
    }).catch(err=>{
        console.log(err);
        // loading_spnr.dismiss();
    });
}

}
