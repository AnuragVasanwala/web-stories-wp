/*
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import styled from 'styled-components';
/**
 * Internal dependencies
 */

import { PaginationArrowLeft } from '../../icons';
import { KEYBOARD_USER_SELECTOR } from '../../constants';

const NavButton = styled.button`
  ${({ theme }) => `
    display: flex;
    align-self: center;
    justify-content: space-around;
    align-items: center;
    contain: content;
    height: 40px;
    width: 40px;
    border-radius: 50%;
    color: ${theme.colors.gray900};
    cursor: pointer;
    background-color: transparent;
    border: ${theme.borders.transparent};
    transition: background-color 300ms ease-in-out, color 300ms ease-in-out;
    padding: 6px;

    &:hover, &:active, &:focus {
      background-color: ${theme.colors.gray800};
      color: ${theme.colors.white};
      
      @media ${theme.breakpoint.largeDisplayPhone} {
        color: ${theme.colors.gray800};
        background-color: transparent;
       }
    }
    ${KEYBOARD_USER_SELECTOR} &:focus {
        border-color: ${theme.colors.action};
      }
    
    &:disabled {
        opacity: 0.3;
        pointer-events: none;
    }

  `}
`;

const PaginationArrow = styled(PaginationArrowLeft)`
  ${({ rotateRight }) => rotateRight && { transform: 'rotate(180deg)' }};
  height: 100%;
`;

export default function PaginationButton({ rotateRight, ...rest }) {
  return (
    <NavButton {...rest}>
      <PaginationArrow aria-hidden rotateRight={rotateRight} />
    </NavButton>
  );
}

PaginationButton.propTypes = {
  rotateRight: PropTypes.bool,
};
