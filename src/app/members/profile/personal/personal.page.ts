import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-personal',
  templateUrl: './personal.page.html',
  styleUrls: ['./personal.page.scss'],
})
export class PersonalPage implements OnInit {
  pDetails: any;
  prsnlDetails: any;
  dpndtsDetails: any;

  constructor(
    private route: ActivatedRoute, 
    private router: Router,
  ) { 
    this.pDetails = 'basicInfo';

    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.prsnlDetails = this.router.getCurrentNavigation().extras.state.details;
        this.dpndtsDetails = this.router.getCurrentNavigation().extras.state.dependents;
      }
    });
  }

  ngOnInit() {
  }

}
