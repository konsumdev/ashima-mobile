import { Directive, ElementRef, Renderer } from '@angular/core';
@Directive({ selector: '[myHighlight]' })
export class MyDirectives {
    constructor(public element: ElementRef, public renderer: Renderer) {
    //    el.nativeElement.style.backgroundColor = 'green';

    //    this.renderer.setElementStyle(this.element.nativeElement, 'background-color', 'red');
    // document.getElementById('input').css();
    // renderer.setElementStyle(element.nativeElement, 'background-color', '#ffa9a9;');
    }

    ngOnInit(){
        // Use renderer to render the emelemt with styles
            this.renderer.setElementStyle(this.element.nativeElement, 'display', 'none');
        
    }
}