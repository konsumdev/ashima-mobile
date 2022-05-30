import { Component, OnInit } from '@angular/core';
import { DashboardProvider } from 'src/app/services/dashboard/dashboard';
import { MyCommonServices } from 'src/app/shared/myCommonServices';

@Component({
    selector: 'app-miss-punch',
    templateUrl: './miss-punch.page.html',
    styleUrls: ['./miss-punch.page.scss'],
})
export class MissPunchPage implements OnInit {
    missPunches: any;
    fetchingAll: boolean = true;

    constructor(
        private dashProv: DashboardProvider,
        public comCtrl: MyCommonServices
    ) { }

    ngOnInit() {
        this.getListing();
    }

    getListing() {
        console.log('get miss');
        this.fetchingAll = true;
        this.dashProv.missedPunchList().then(res=>{
            
            if (res) {
                let rslt: any = res;
                this.missPunches = rslt.list;
                this.fetchingAll = false;
            }
        }).catch(err=>{
            this.comCtrl.presentToast('', 'api_error');
            this.fetchingAll = false;
            console.log(err);
        });
    }

}
