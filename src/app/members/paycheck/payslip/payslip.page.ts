import { Component, OnInit, ViewChild } from '@angular/core';
import { IonSlides } from '@ionic/angular';
import { AppComponent } from 'src/app/app.component';
import { PaycheckProvider } from 'src/app/services/paycheck/paycheck';

@Component({
  selector: 'app-payslip',
  templateUrl: './payslip.page.html',
  styleUrls: ['./payslip.page.scss'],
})
export class PayslipPage implements OnInit {

    @ViewChild(IonSlides, {static:false}) slides: IonSlides;

    pDetails        : any;
    selectedSegment : any;
    regularHrs      : boolean;
    adjstmntShow    : boolean;
    allwncShow      : boolean;
    cmmssnShow      : boolean;
    dmnmsShow       : boolean;
    oearnShow       : boolean;
    insrnceShow     : boolean;
    thrdprtyShow    : boolean;
    govloanShow     : boolean;
    othrdedShow     : boolean;
    othradjShow     : boolean;
    latestPay       : any;
    currentPage     : number = 1;
    totalPages      : any;
    psDetails       : any;
    fetchingLatest  : boolean = true;
    fetchingList    : boolean = true;
    isLatestDraft   : boolean = false;
    fakeUsers       : Array<any>;
    ps_view         : any;

    slideOpts = {
      initialSlide: 0,
      speed: 400
    };

    constructor(
      private mainApp : AppComponent,
      private payPro: PaycheckProvider,
    ) {
      this.fakeUsers = new Array(10);
      this.mainApp.apiSessionChecker();

      this.regularHrs     = false;
      this.adjstmntShow   = false;
      this.allwncShow     = false;
      this.cmmssnShow     = false;
      this.dmnmsShow      = false;
      this.oearnShow      = false;
      this.insrnceShow    = false;
      this.thrdprtyShow   = false;
      this.govloanShow    = false;
      this.othrdedShow    = false;
      this.othradjShow    = false;
      this.fetchingLatest = true;
      this.fetchingList   = true;
      this.isLatestDraft  = false;

      this.pDetails = 'latest';
      this.selectedSegment = 'latest';
      
    }

    ngOnInit() {
        this.getLatestPayslip();
        this.getPayslipList();
    }

    getPayslipList() {
      this.payPro.getAllPayslip(this.currentPage+'').then( res => {
          let psRes: any = res;

          this.psDetails = psRes.list;
          this.totalPages = parseInt(psRes.total, 10);
          this.currentPage = parseInt(psRes.page, 10);
          this.fetchingList   = false;
          console.log(this.psDetails);
      }).catch(error => {
          console.log(error);
          this.fetchingList   = false;
      });
    }

    getLatestPayslip() {
      this.payPro.getCurrentPayslip().then( res => {
        let result: any = res;

        if (result.result) {
            this.latestPay = result.payslip;
            this.isLatestDraft = result.is_draft;
            this.ps_view = this.latestPay.ps_settings;
            // console.log(this.ps_view);
        }

        this.fetchingLatest = false;
      }).catch(error => {
          console.log(error);
          this.fetchingLatest = false;
      });
    }

}
